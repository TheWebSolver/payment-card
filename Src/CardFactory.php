<?php
/**
 * Payment Card factory.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use Generator;
use Throwable;
use TypeError;
use InvalidArgumentException;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

/**
 * @phpstan-type CardSchema array{
 *  type?:      string,
 *  classname?: string,
 *  checkLuhn?: bool,
 *  name:       string,
 *  alias:      string,
 *  breakpoint: (string|int)[],
 *  code:       array{0:string, 1:int},
 *  length:     array<int,string|int|array<int,string|int>>,
 *  idRange:    array<int,string|int|array<int,string|int>>,
 * }
 */
class CardFactory {
	public const CREDIT_CARD  = 'Credit Card';
	public const DEBIT_CARD   = 'Debit Card';
	public const DEFAULT_CARD = 'Payment Card';

	/**
	 * Possible array keys and their values' datatype Schema for a Payment Card.
	 *
	 * - If `type` key not passed, Payment Card is treated as a Credit Card.
	 * - If `classname` key not passed, anonymous class is used.
	 */
	public const CARD_SCHEMA = array(
		'type?'      => 'string',
		'classname?' => 'string',
		'checkLuhn?' => 'bool',
		'name'       => 'string',
		'alias'      => 'string',
		'breakpoint' => 'int[]',
		'code'       => 'array{0:string,1:int}',
		'length'     => 'array<int,string|int|array<int,string|int>>',
		'idRange'    => 'array<int,string|int|array<int,string|int>>',
	);

	/** @var array<mixed> */
	private array $content;

	private string $path = '';

	/**
	 * List of Payment Card instances for `PaymentCard` enums.
	 *
	 * @var Card[]
	 */
	private static array $cards;

	public function __construct( mixed $data = null /* $fileType: for internal use only */ ) {
		if ( null !== $data ) {
			if ( 2 === func_num_args() && ( $fileType = func_get_arg( position: 1 ) ) && is_string( $fileType ) ) {
				$type = $fileType;
			}

			$this->resolvePayloadContent( $data, $type ?? '' );
		}
	}

	/** @param string|mixed[] $data */
	public function withPayload( string|array $data ): self {
		$this->resolvePayloadContent( $data );

		return $this;
	}

	/**
	 * @param string  $index The JSON key.
	 * @param mixed[] $args  Never used.
	 * @throws TypeError When something went wrong.
	 * @access private
	 */
	public static function __callStatic( string $index, array $args ): Card {
		$slash         = DIRECTORY_SEPARATOR;
		self::$cards ??= ( new self() )
			->withPayload( data: dirname( __DIR__ ) . $slash . 'Resource' . $slash . 'paymentCards.json' )
			->createCards();

		return self::$cards[ $index ] ?? self::shutdownForInvalidJsonKey( $index );
	}

	/**
	 * @return array<string|int,Card>|Generator
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 * @phpstan-return ($lazyload is true ? Generator : array<string|int,Card>)
	 */
	public static function createFromPhpFile(
		string $path,
		bool $preserveKeys = true,
		bool $lazyload = false
	): array|Generator {
		$factory       = ( new self( ...self::getPhpContent( $path ) ) );
		$factory->path = $path;

		return $lazyload ? $factory->lazyLoadCards( $preserveKeys ) : $factory->createCards( $preserveKeys );
	}

	/**
	 * @return array<string|int,Card>|Generator
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 * @phpstan-return ($lazyload is true ? Generator : array<string|int,Card>)
	 */
	public static function createFromJsonFile(
		string $path,
		bool $preserveKeys = true,
		bool $lazyload = false
	): array|Generator {
		$factory       = ( new self( ...self::getJsonContent( $path ) ) );
		$factory->path = $path;

		return $lazyload ? $factory->lazyLoadCards( $preserveKeys ) : $factory->createCards( $preserveKeys );
	}

	/**
	 * @return array<string|int,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 * @phpstan-return ($lazyload is true ? Generator : array<string|int,Card>)
	 */
	public static function createFromFile(
		string $path,
		bool $preserveKeys = true,
		bool $lazyload = false
	): array|Generator {
		$factory       = new self( ...self::parseContentIfFile( $path ) );
		$factory->path = $path;

		return $lazyload ? $factory->lazyLoadCards( $preserveKeys ) : $factory->createCards( $preserveKeys );
	}

	/**
	 * @return array<string|int,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public function createCards( bool $preserveKeys = true ): array {
		/** @var array<string|int,Card> */
		return iterator_to_array( $this->lazyLoadCards( $preserveKeys ), $preserveKeys );
	}

	public function lazyLoadCards( bool $preserveKeys = true ): Generator {
		foreach ( $this->content as $index => $args ) {
			if ( $preserveKeys ) {
				yield $index => $this->createCard( $index );
			} else {
				yield $this->createCard( $index );
			}
		}
	}

	/** @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`. */
	public function createCard( string|int|null $index = null ): Card {
		$args = $index
			? $this->content[ $index ]
			: ( array_is_list( $this->content ) ? reset( $this->content ) : $this->content );

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
			$this->shutdownForInvalidSchema( $args, $index, $e );
		}
	}

	private function resolvePayloadContent( mixed $data, string $type = '' ): void {
		if ( $this->content ?? false ) {
			return;
		}

		[ $content, $typeWithPath, $this->path ] = self::parseContentIfFile( $data );

		if ( is_array( $content ) ) {
			$this->content = $content;

			return;
		}

		if ( $type ) {
			$typeWithPath = $type;
		}

		self::shutdownForInvalidFile( $typeWithPath );
	}

	/** @param array<string,mixed> $args */
	private function getCardInstance( array $args ): Card {
		$cardType = isset( $args['type'] ) && is_string( $card = $args['type'] ) ? $card : self::CREDIT_CARD;
		$concrete = $args['classname'] ?? '';
		$checkLuhn = isset( $args['checkLuhn'] ) && is_bool( $luhn = $args['checkLuhn'] ) ? $luhn : true;

		return $concrete && is_string( $concrete ) && is_a( $concrete, Card::class, allow_string: true )
			? new $concrete( $cardType, $checkLuhn )
			: new class( $cardType, $checkLuhn ) extends CardType {
				public function __construct( string $type, bool $checkLuhn ) {
					parent::__construct( $type, $checkLuhn );
				}
			};
	}

	/** @return array{0:mixed,1:string,2:string} */
	private static function parseContentIfFile( mixed $payload ): array {
		if ( ! is_string( $payload ) || ! is_readable( $payload ) ) {
			return array( $payload, 'file type', '' );
		}

		return match ( true ) {
			default                              => array( '', 'file: ' . $payload, $payload ),
			self::isFileType( $payload, 'json' ) => self::getJsonContent( $payload ),
			self::isFileType( $payload, 'php' )  => self::getPhpContent( $payload )
		};
	}

	private static function isFileType( string $file, string $ext ): bool {
		return substr( $file, offset: - strlen( $ext ) ) === $ext;
	}

	/** @return array{0:mixed,1:string,2:string} */
	private static function getPhpContent( string $file ): array {
		$content = require $file;
		$content = is_callable( $content ) ? $content() : $content;

		return array( $content, 'php file: ' . $file, $file );
	}

	/** @return array{0:mixed,1:string,2:string} */
	private static function getJsonContent( string $file ): mixed {
		$type    = 'JSON file: ' . $file;
		$content = file_get_contents( $file );

		if ( false === $content ) {
			self::shutdownForInvalidFile( $type );
		}

		return array( json_decode( $content, associative: true ), $type, $file );
	}

	private static function shutdownIfNonAssociative( mixed $args ): void {
		if ( is_array( $args ) && ! array_is_list( $args ) ) {
			return;
		}

		$schema = '';
		$isLast = array_key_last( self::CARD_SCHEMA );

		foreach ( self::CARD_SCHEMA as $key => $type ) {
			$schema .= $key . ':' . $type . ( $isLast === $key ? '' : ', ' );
		}

		throw new TypeError(
			sprintf(
				'Invalid data provided for creating card. The data must be an associative array with schema: %s',
				'array{' . $schema . '}'
			)
		);
	}

	private static function shutdownForInvalidFile( string $typeWithPath ): never {
		throw new TypeError(
			sprintf( 'Invalid %s provided for creating cards. File must return an array data.', $typeWithPath )
		);
	}

	/** @param mixed[] $args */
	private function shutdownForInvalidSchema( array $args, string|int|null $index, Throwable $e ): never {
		throw new TypeError(
			previous: $e,
			message: sprintf(
				'Invalid Payment Card arguments given%1$s%2$s.%5$sGiven argument: %3$s%5$sError message: %4$s.',
				/* %1 */ null !== $index ? ' for array key [#' . $index . ']' : '',
				/* %2 */ $this->path ? ' in file "' . $this->path . '"' : '',
				/* %3 */ json_encode( $args ),
				/* %4 */ $e->getMessage(),
				/* %5 */ PHP_EOL,
			)
		);
	}

	private static function shutdownForInvalidJsonKey( string $key ): never {
		throw new TypeError( sprintf( 'Impossible to find Card instance from given JSON key: %s', $key ) );
	}
}
