<?php
/**
 * The Payment Card factory.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TypeError;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

class CardFactory {
	public const CREDIT_CARD  = 'Credit Card';
	public const DEBIT_CARD   = 'Debit Card';
	public const DEFAULT_CARD = 'Payment Card';

	/** If `type` key not passed, Payment Card is treated as a Credit Card. */
	public const CARD_SCHEMA = array(
		'type?'      => 'string',
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

	public function __construct( mixed $data /* $datatype: for internal use only */ ) {
		[ $content, $type ] = self::maybeParseDataFromFile( $data );

		if ( $type ) {
			$this->path = $data;
		}

		if ( is_array( $content ) ) {
			$this->content = $content;

			return;
		}

		if ( 2 === func_num_args() && $datatype = func_get_arg( position: 1 ) ) {
			$type = $datatype;
		}

		self::shutdownFactoryForFile( $type );
	}

	/**
	 * @return array<string,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public static function createFromPhpFile( string $path ): array {
		return self::createFrom( $path );
	}

	/**
	 * @return array<string,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public static function createFromJsonFile( string $path ): array {
		return self::createFrom( $path );
	}

	/**
	 * @return array<string,Card>
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public function createCards(): array {
		$cards = array();

		foreach ( $this->content as $index => $args ) {
			self::maybeShutdownFactoryForNonAssociativeArray( $args );

			// @phpstan-ignore-next-line -- $args Type hint checked by setter methods.
			$card                       = $this->createCard( $args, $index );
			$cards[ $card->getAlias() ] = $card;
		}

		return $cards;
	}

	/**
	 * @param array{name:string,alias:string,breakpoint:(string|int)[],code:array{0:string,1:int},length:(string|int|(string|int)[])[],idRange:(string|int|(string|int)[])[],type?:string} $args
	 * @throws TypeError When $args passed does not match the `CardFactory::CARD_SCHEMA`.
	 */
	public function createCard( array $args, string|int|null $currentIndex = null ): Card {
		try {
			return $this->getCardClass( forType: $args['type'] ?? self::CREDIT_CARD )
				->setName( $args['name'] )
				->setAlias( $args['alias'] )
				->setBreakpoint( ...$args['breakpoint'] )
				->setCode( ...$args['code'] )
				->setLength( $args['length'] )
				->setIdRange( $args['idRange'] );
		} catch ( TypeError $e ) {
			throw new TypeError(
				previous: $e,
				message: sprintf(
					'Invalid Payment Card arguments given%1$s%2$s.%4$sGiven argument: %5$s%4$sError message: %3$s.',
					null !== $currentIndex ? ' for array key [#' . func_get_arg( 1 ) . ']' : '',
					$this->path ? ' in file "' . $this->path . '"' : '',
					$e->getMessage(),
					PHP_EOL,
					json_encode( $args )
				)
			);
		}//end try
	}

	private function getCardClass( string $forType ): Card {
		return new class( $forType ) extends CardType {
			public function __construct( private readonly string $cardType ) {
				parent::__construct();
			}

			protected function getType(): string {
				return $this->cardType;
			}
		};
	}

	/** @return array<string,Card> */
	private static function createFrom( string $filePath ): array {
		$factory       = new self( ...self::maybeParseDataFromFile( $filePath ) );
		$factory->path = $filePath;

		return $factory->createCards();
	}

	/** @return array{0:mixed,1:string} */
	private static function maybeParseDataFromFile( mixed $data ): array {
		if ( ! is_string( $data ) || ! is_readable( $data ) ) {
			return array( $data, '' );
		}

		if ( substr( $data, offset: -4 ) === 'json' ) {
			$type    = 'JSON file: ' . $data;
			$content = file_get_contents( $data );

			if ( false === $content ) {
				self::shutdownFactoryForFile( $type );
			}

			return array( json_decode( $content, associative: true ), $type );
		}

		if ( substr( $data, offset: -3 ) === 'php' ) {
			$content = require $data;
			$content = is_callable( $content ) ? $content() : $content;

			return array( $content, 'php file: ' . $data );
		}

		return array( '', 'file: ' . $data );
	}

	private static function maybeShutdownFactoryForNonAssociativeArray( mixed $args ): void {
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

	private static function shutdownFactoryForFile( string $type ): never {
		throw new TypeError(
			sprintf( 'Invalid %s provided for creating cards. File must return an array data.', $type ?: 'file type' )
		);
	}
}
