<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use Generator;
use Throwable;
use TypeError;
use RuntimeException;
use InvalidArgumentException;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

final class CardFactory {
	public const CREDIT_CARD   = 'Credit Card';
	public const DEBIT_CARD    = 'Debit Card';
	public const DEFAULT_CARD  = 'Payment Card';
	public const RESOURCE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resource';

	/**
	 * Possible array keys and their values' datatype Schema for a Payment Card.
	 *
	 * - If `type` key not passed, Payment Card is treated as a Credit Card.
	 * - If `classname` key not passed, anonymous class is used.
	 * - If `checkLuhn` key not passed, Luhn algorithm is always checked.
	 */
	public const CARD_SCHEMA = [
		'type?'      => 'string',
		'classname?' => 'class-string<' . Card::class . '>',
		'checkLuhn?' => 'bool',
		'name'       => 'string',
		'alias'      => 'string',
		'breakpoint' => 'list<int>',
		'code'       => 'array{name:string,size:int}',
		'length'     => 'list<int|list<int>>',
		'idRange'    => 'list<int|list<int>>',
	];

	/** @placeholder: `%s:` Index key to get Card instance. */
	public const INVALID_INDEX_KEY = 'Impossible to find Card instance from given index: %s';
	/** @placeholder: `%s:` File path. */
	public const INVALID_PAYLOAD_PATH = 'Invalid %s provided for creating cards. File must return an array data.';
	/** @placeholder: `%s:` Card Schema. */
	public const NON_ASSOCIATIVE_PAYLOAD = 'Invalid data provided for creating card. It must be an associative array with schema: array{%s}';
	/** @placeholder `1:` Index details, `2:` Path details, `3:` JSON encoded args, `4:`, Previous exception msg, `5:` End of line. */
	public const INVALID_PAYLOAD_SCHEMA = 'Invalid Payment Card arguments given%1$s%2$s.%5$sGiven argument: %3$s%5$sError message: %4$s.';
	public const NON_RESOLVABLE_PAYLOAD = 'Unable to resolve payload for creating Card Type. The payload was neither a valid resource path nor a non-empty array of Card Type Schema.';

	/** @var non-empty-array<mixed> */
	private array $payload;
	/** @var non-empty-string */
	private string $filePath;
	private string $fileType = '';

	/** @var ?class-string<Card> */
	private static ?string $defaultCardClass;

	/** @param class-string<Card> $classname */
	public static function setGlobalCardClass( string $classname ): void {
		self::$defaultCardClass ??= $classname;
	}

	public static function resetGlobalCardClass(): void {
		self::$defaultCardClass = null;
	}

	/**
	 * @param non-empty-string           $path            The payload resource path.
	 * @param list<int|non-empty-string> $indicesToCreate Only payload indices that should create card instance.
	 * @return ($indicesToCreate is empty ? Generator<array-key,Card> : Generator<array-key,?Card>)
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public static function createFromFile( string $path, array $indicesToCreate = [] ): Generator {
		$factory           = new self( indicesToCreate: $indicesToCreate );
		$factory->filePath = $path;

		return $factory->lazyLoadCards();
	}

	/**
	 * @param string|mixed[]|null        $payload         The payload resource path or a Single Card Schema array or an array of Card Schemas array.
	 * @param list<int|non-empty-string> $indicesToCreate Only payload indices that should create card instance.
	 */
	public function __construct( string|array|null $payload = null, public readonly array $indicesToCreate = [] ) {
		$payload && $this->withPayload( $payload );
	}

	/**
	 * @throws RuntimeException When payload cannot be resolved.
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public function createCard( string|int|null $payloadIndex = null ): Card {
		$this->resolvePayloadContent();

		$args = $payloadIndex
			? $this->payload[ $payloadIndex ]
			: ( array_is_list( $this->payload ) ? $this->payload[0] : $this->payload );

		self::shutdownIfNonAssociative( $args );

		try {
			return $this->getCardInstance( $args )
				->setName( $args['name'] )
				->setAlias( $args['alias'] )
				->setBreakpoint( ...$args['breakpoint'] )
				->setCode( ...$args['code'] )
				->setLength( $args['length'] )
				->setIdRange( $args['idRange'] );
		} catch ( TypeError | InvalidArgumentException $e ) {
			$this->shutdownForInvalidSchema( $args, $payloadIndex, $e );
		}
	}

	/**
	 * @return Generator<array-key,?Card>
	 * @throws RuntimeException When payload cannot be resolved.
	 */
	public function lazyLoadCards(): Generator {
		$this->resolvePayloadContent();

		$onlyIndices = $this->indicesToCreate;
		$generator   = $this->lazyloadCardsBySentPayloadIndex();

		foreach ( $this->payload as $index => $args ) {
			yield $index => $generator->send( ! $onlyIndices || in_array( $index, $onlyIndices, strict: true ) );
		}
	}

	/**
	 * Creates Card instances lazily based on payload index sent and matching it with the current index before yield.
	 *
	 * @return Generator<array-key,?Card> Returns Card instance or null based on sent value.
	 * @throws RuntimeException When payload cannot be resolved.
	 * @see CardFactory::lazyloadCards()
	 * ```
	 */
	public function lazyloadCardsBySentPayloadIndex(): Generator {
		$this->resolvePayloadContent();

		$card = null;

		foreach ( $this->payload as $index => $args ) {
			$sent = ( yield $index => $card );
			$card = ! isset( $sent ) ? $card : $this->maybeCreateCardForIndex( $sent, $index );
		}

		// The last one is never yielded, so we handle it here.
		if ( isset( $sent ) && $sent ) {
			yield $index => $this->maybeCreateCardForIndex( $sent, $index );
		}
	}

	private function maybeCreateCardForIndex( mixed $sent, string|int $index ): ?Card {
		return match ( true ) {
			is_string( $sent ), is_int( $sent ) => $sent === $index ? $this->createCard( $index ) : null,
			is_bool( $sent )                    => $sent ? $this->createCard( $index ) : null,
			default                             => null,
		};
	}

	/** @param string|array<mixed> $payload The payload resource path or a Single Card Schema array or an array of Card Schemas array. */
	private function withPayload( string|array $payload ): self {
		if ( is_string( $payload ) && ! empty( $payload ) ) {
			$this->filePath = $payload;
		} elseif ( ! empty( $payload ) ) {
			$this->payload = $payload;
		}

		return $this;
	}

	private function resolvePayloadContent(): void {
		$this->payload ??= ! is_array( $content = $this->parseContentFromFilepath() ) || empty( $content )
			? throw new RuntimeException( self::NON_RESOLVABLE_PAYLOAD )
			: $content;
	}

	/** @param array<string,mixed> $args */
	private function getCardInstance( array $args ): Card {
		[ $type, $classname, $checkLuhn ] = $this->polyfill( $args );

		return new $classname( $type, $checkLuhn );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array{0:string,1:class-string<Card>,2:bool}
	 */
	private function polyfill( array $args ): array {
		$class   = $args['classname'] ?? null;
		$default = self::$defaultCardClass ?? CardType::class;

		return [
			is_string( $card = ( $args['type'] ?? null ) ) ? $card : self::CREDIT_CARD,
			is_string( $class ) && is_a( $class, Card::class, allow_string: true ) ? $class : $default,
			is_bool( $luhn = ( $args['checkLuhn'] ?? null ) ) ? $luhn : true,
		];
	}

	private function parseContentFromFilepath(): mixed {
		return match ( true ) {
			! is_readable( $this->filePath ?? '' )   => null,
			str_ends_with( $this->filePath, 'json' ) => $this->parseJsonContent(),
			str_ends_with( $this->filePath, 'php' )  => $this->parsePhpContent(),
			default                                  => self::invalidFile(
				( $this->fileType ? strtoupper( $this->fileType ) . ' ' : '' ) . "file: {$this->filePath}"
			),
		};
	}

	private function parsePhpContent(): mixed {
		$this->fileType = 'php';
		$content        = require $this->filePath;

		return is_callable( $content ) ? $content() : $content;
	}

	private function parseJsonContent(): mixed {
		$this->fileType = 'json';

		return ( false !== $json = file_get_contents( $this->filePath ) )
			? json_decode( $json, associative: true )
			: self::invalidFile( 'JSON file: ' . $this->filePath );
	}

	private static function shutdownIfNonAssociative( mixed $args ): void {
		if ( is_array( $args ) && ! array_is_list( $args ) ) {
			return;
		}

		$schema = '';
		$isLast = array_key_last( self::CARD_SCHEMA );

		foreach ( self::CARD_SCHEMA as $key => $type ) {
			if ( str_ends_with( haystack: $key, needle: '?' ) ) {
				continue;
			}

			$schema .= $key . ':' . $type . ( $isLast === $key ? '' : ', ' );
		}

		throw new TypeError( sprintf( self::NON_ASSOCIATIVE_PAYLOAD, $schema ) );
	}

	private static function invalidFile( string $typeWithPath ): never {
		throw new TypeError( sprintf( self::INVALID_PAYLOAD_PATH, $typeWithPath ) );
	}

	/**
	 * @param mixed[] $args
	 * @throws TypeError When invalid card args given.
	 */
	private function shutdownForInvalidSchema( array $args, string|int|null $index, Throwable $e ): never {
		throw new TypeError(
			previous: $e,
			message: sprintf(
				self::INVALID_PAYLOAD_SCHEMA,
				/* 1: */ null !== $index ? ' for array key [#' . $index . ']' : '',
				/* 2: */ $this->filePath ? ' in file "' . $this->filePath . '"' : '',
				/* 3: */ json_encode( $args ),
				/* 4: */ $e->getMessage(),
				/* 5: */ PHP_EOL,
			)
		);
	}
}
