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

	public function __construct( mixed $data /* $fileType: for internal use only */ ) {
		[ $content, $typeWithPath, $this->path ] = self::parseContentIfFile( $data );

		if ( is_array( $content ) ) {
			$this->content = $content;

			return;
		}

		if ( 2 === func_num_args() && ( $fileType = func_get_arg( position: 1 ) ) && is_string( $fileType ) ) {
			$typeWithPath = $fileType;
		}

		self::shutdownForInvalidFile( $typeWithPath );
	}

	/**
	 * @param string  $index The JSON key.
	 * @param mixed[] $args  Never used.
	 * @throws TypeError When something went wrong.
	 * @access private
	 */
	public static function __callStatic( string $index, array $args ): Card {
		self::$cards ??= ( new self(
			data: dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . 'paymentCards.json'
		) )->createCards();

		return self::$cards[ $index ] ?? self::shutdownForInvalidJsonKey( $index );
	}

	/**
	 * @return array<string|int,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public static function createFromPhpFile( string $path ): array {
		return self::createFrom( $path );
	}

	/**
	 * @return array<string|int,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public static function createFromJsonFile( string $path ): array {
		return self::createFrom( $path );
	}

	/**
	 * @return array<string|int,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public function createCards( bool $preserveKeys = true ): array {
		/** @var array<string|int,Card> */
		return iterator_to_array( iterator: $this->yieldCard(), preserve_keys: $preserveKeys );
	}

	public function yieldCard(): Generator {
		foreach ( $this->content as $index => $args ) {
			yield $index => $this->createCard( $index );
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

	/** @param array<string,string> $args */
	private function getCardInstance( array $args ): Card {
		$cardType = $args['type'] ?? self::CREDIT_CARD;
		$concrete = $args['classname'] ?? '';

		return $concrete && is_a( $concrete, Card::class, allow_string: true )
			? new $concrete( $cardType )
			: new class( $cardType ) extends CardType {
				public function __construct( string $type ) {
					parent::__construct( $type );
				}
			};
	}

	/** @return array<string|int,Card> */
	private static function createFrom( string $file ): array {
		$factory       = new self( ...self::parseContentIfFile( $file ) );
		$factory->path = $file;

		return $factory->createCards();
	}

	/** @return array{0:mixed,1:string,2:string} */
	private static function parseContentIfFile( mixed $fileOrContent ): array {
		if ( ! is_string( $fileOrContent ) || ! is_readable( $fileOrContent ) ) {
			return array( $fileOrContent, 'file type', '' );
		}

		if ( substr( $fileOrContent, offset: -4 ) === 'json' ) {
			$type    = 'JSON file: ' . $fileOrContent;
			$content = file_get_contents( $fileOrContent );

			if ( false === $content ) {
				self::shutdownForInvalidFile( $type );
			}

			return array( json_decode( $content, associative: true ), $type, $fileOrContent );
		}

		if ( substr( $fileOrContent, offset: -3 ) === 'php' ) {
			$content = require $fileOrContent;
			$content = is_callable( $content ) ? $content() : $content;

			return array( $content, 'php file: ' . $fileOrContent, $fileOrContent );
		}

		return array( '', 'file: ' . $fileOrContent, $fileOrContent );
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
