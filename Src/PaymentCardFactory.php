<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use Closure;
use Generator;
use Throwable;
use TypeError;
use RuntimeException;
use OutOfBoundsException;
use InvalidArgumentException;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\Event\PaymentCardCreated;

/** @template-implements CardFactory<PaymentCard,PaymentCardCreated> */
class PaymentCardFactory implements CardFactory {
	public const DEFAULT_CARD_TYPE = 'Credit Card';

	/**
	 * Possible array keys and their values' datatype Schema for a Payment Card.
	 *
	 * - If `type` key not passed, Payment Card is treated as a Credit Card.
	 * - If `classname` key not passed, base `PaymentCard` class is used.
	 * - If `checkLuhn` key not passed, Luhn algorithm is always checked.
	 */
	public const CARD_SCHEMA = [
		'type?'      => 'string',
		'classname?' => 'class-string<' . PaymentCard::class . '>',
		'checkLuhn?' => 'bool',
		'name'       => 'string',
		'alias'      => 'string',
		'breakpoint' => 'list<int>',
		'code'       => 'array{name:string,size:int}',
		'length'     => 'list<int|list<int>>',
		'idRange'    => 'list<int|list<int>>',
	];

	/** @placeholder: `%s:` Resource filepath where payload data exists */
	public const INVALID_PAYLOAD_PATH = 'Invalid %s provided for creating cards. File must return an array data.';
	/** @placeholder: `%s:` Provided payload index */
	public const UNDEFINED_PAYLOAD_INDEX = 'Impossible to create Payment Card instance for undefined payload index: "%s".';
	/** @placeholder `1:` Index details, `2:` Path details, `3:` JSON encoded args, `4:`, Previous exception msg, `5:` End of line */
	public const INVALID_PAYLOAD_SCHEMA = 'Invalid Payment Card arguments given%1$s%2$s.%5$sGiven argument: %3$s%5$sError message: %4$s.';
	public const NON_RESOLVABLE_PAYLOAD = 'Unable to resolve payload for creating Card Type. The payload was neither a valid resource path nor a non-empty array of Card Type Schema.';

	/** @var non-empty-array<mixed> */
	private array $payload;
	/** @var non-empty-string */
	private string $filePath;
	private string $fileType = '';

	/** @var ?class-string<PaymentCard> */
	private static ?string $defaultCardClass;

	/** @param class-string<PaymentCard> $classname */
	public static function setGlobalCardClass( string $classname ): void {
		self::$defaultCardClass ??= $classname;
	}

	public static function resetGlobalCardClass(): void {
		self::$defaultCardClass = null;
	}

	/**
	 * @param non-empty-string           $path            The payload resource path.
	 * @param list<int|non-empty-string> $indicesToCreate Only payload indices that should create card instance.
	 * @throws TypeError When $args passed does not match the Payment Card schema.
	 */
	public static function createFromFile( string $path, array $indicesToCreate = [] ): static {
		$factory           = new static( payload: [], indicesToCreate: $indicesToCreate );
		$factory->filePath = $path;

		return $factory;
	}

	/**
	 * @param string|mixed[]             $payload         The payload resource path or a Single Card Schema array or an array of Card Schemas array.
	 * @param list<int|non-empty-string> $indicesToCreate Only payload indices that should create card instance.
	 */
	final public function __construct( string|array $payload, private readonly array $indicesToCreate = [] ) {
		$this->withPayload( $payload );
	}

	public function getPayload(): array {
		return $this->payload;
	}

	public function getCreatableIndices(): array {
		return $this->indicesToCreate;
	}

	public function create( string|int $payloadIndex ): PaymentCard {
		$this->resolvePayloadContent();

		$args = $this->payload[ $payloadIndex ]
			?? throw new OutOfBoundsException( sprintf( self::UNDEFINED_PAYLOAD_INDEX, $payloadIndex ) );

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

	public function lazyload( ?Closure $eventHandler = null ): Generator {
		$this->resolvePayloadContent();

		$onlyIndices = $this->getCreatableIndices();
		$generator   = $this->lazyloadSentPayloadIndexOnly();

		foreach ( $this->payload as $index => $args ) {
			$isCreatable = ! $onlyIndices || in_array( $index, $onlyIndices, strict: true );
			$card        = $generator->send( $isCreatable );
			$yieldNext   = $eventHandler ? $eventHandler( new PaymentCardCreated( $card, $index, $args, $isCreatable ) ) : true;

			yield $index => $card;

			if ( ! $yieldNext ) {
				return;
			}
		}
	}

	/**
	 * Creates Card instance lazily based on payload index sent and matching it with the current index before yield.
	 *
	 * @return Generator<array-key,?PaymentCard> Returns Card instance or null based on sent value.
	 * @throws RuntimeException When payload cannot be resolved.
	 * @see CardFactory::lazyload()
	 */
	public function lazyloadSentPayloadIndexOnly(): Generator {
		$this->resolvePayloadContent();

		$card = null;

		foreach ( $this->payload as $index => $args ) {
			$sent = ( yield $index => $card );
			$card = ! isset( $sent ) ? $card : $this->maybeCreateForIndex( $sent, $index );
		}

		// The last one is never yielded, so we handle it here.
		if ( isset( $sent ) && $sent ) {
			yield $index => $this->maybeCreateForIndex( $sent, $index );
		}
	}

	private function maybeCreateForIndex( mixed $sent, string|int $index ): ?PaymentCard {
		return match ( true ) {
			is_string( $sent ), is_int( $sent ) => $sent === $index ? $this->create( $index ) : null,
			is_bool( $sent )                    => $sent ? $this->create( $index ) : null,
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
	private function getCardInstance( array $args ): PaymentCard {
		[ $type, $classname, $checkLuhn ] = $this->polyfill( $args );

		return new $classname( $type, $checkLuhn );
	}

	/**
	 * @param array<string,mixed> $args
	 * @return array{string,class-string<PaymentCard>,bool}
	 */
	private function polyfill( array $args ): array {
		$class   = $args['classname'] ?? null;
		$default = self::$defaultCardClass ?? PaymentCardType::class;

		return [
			is_string( $card = ( $args['type'] ?? null ) ) ? $card : self::DEFAULT_CARD_TYPE,
			is_string( $class ) && is_a( $class, PaymentCard::class, allow_string: true ) ? $class : $default,
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
