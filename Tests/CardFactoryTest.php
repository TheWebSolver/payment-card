<?php
/**
 * Card Factory test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use TypeError;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Test\Resource\NapasCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

class CardFactoryTest extends TestCase {
	public function testGlobalCardClassSetterResetter(): void {
		CardFactory::setGlobalCardClass( NapasCard::class );

		$payload = array(
			array(
				'name'       => 'Test Card',
				'alias'      => 'test-card',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVC', 3 ),
				'length'     => array( 16, 19 ),
				'idRange'    => array( 9704 ),
			),
			array(
				'name'       => 'Another',
				'alias'      => 'another',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVV', 3 ),
				'length'     => array( 16 ),
				'idRange'    => array( 9860 ),
			),
		);

		foreach ( ( new CardFactory( $payload ) )->lazyLoadCards() as $card ) {
			$this->assertInstanceOf( NapasCard::class, actual: $card );
		}

		CardFactory::resetGlobalCardClass();
	}

	public function testCardCreationFromArray(): void {
		$napas = array(
			'name'       => 'Napas',
			'alias'      => 'napas',
			'classname'  => NapasCard::class,
			'breakpoint' => array( 4, 8, 12 ),
			'code'       => array( 'CVC', 3 ),
			'length'     => array( 16, 19 ),
			'idRange'    => array( 9704 ),
		);

		$payload = array(
			$napas,
			array(
				'name'       => 'Gerbang Pembayaran Nasional',
				'alias'      => 'gpn',
				'type'       => 'Debit Card',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVC', 3 ),
				'length'     => array( 16, 18, 19 ),
				'idRange'    => array( 1946, 50, 56, 58, array( 60, 63 ) ),
			),
			array(
				'name'       => 'Humo',
				'alias'      => 'humo',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVV', 3 ),
				'length'     => array( 16 ),
				'idRange'    => array( 9860 ),
			),
		);

		$factory = new CardFactory( data: $payload );
		$loader  = $factory->lazyLoadCards();

		$this->assertSame( expected: 'napas', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'gpn', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'humo', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertNull( $loader->current() );

		$cards = ( new CardFactory( $payload ) )->createCards();

		$this->assertSame(
			expected: array( 'napas', 'gpn', 'humo' ),
			actual: array_map( static fn( $c ) => $c->getAlias(), array: $cards )
		);

		$this->assertCount(
			expectedCount: 3,
			haystack: array_filter( $cards, static fn( $c ) => $c instanceof Card )
		);

		$this->assertCount(
			expectedCount: 2,
			haystack: array_filter( $cards, static fn( $c ) => ( new ReflectionClass( $c ) )->isAnonymous() )
		);

		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $napas ) )->createCard() );
		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $payload ) )->createCard() );
		$this->assertSame( 'humo', actual: ( new CardFactory( $payload ) )->createCard( 2 )->getAlias() );
	}

	public function testCardCreationFromJsonFile(): void {
		$path    = __DIR__ . '/Resource/Cards.json';
		$cards   = CardFactory::createFromJsonFile( $path );
		$aliases = array( 'napas', 'gpn', 'humo' );

		$this->assertCount( expectedCount: 3, haystack: $cards );
		$this->assertCreatedCardAliasesMatch( (array) $cards, $aliases );
		$this->assertAllCardsAreRegistered( (array) $cards );

		$altCards = CardFactory::createFromFile( $path );

		foreach ( $cards as $index => $card ) {
			$this->assertTrue( $altCards[ $index ]->getName() === $card->getName() );
		}

		$cards = CardFactory::createFromJsonFile( $path, lazyload: true );

		while ( $cards->valid() ) {
			$this->assertSame( expected: $aliases[ $cards->key() ], actual: $cards->current()->getAlias() );
			$cards->next();
		}

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		CardFactory::createFromJsonFile( $path );
	}

	/**
	 * @param string[] $aliases
	 * @dataProvider providePhpFiles
	 */
	public function testCardCreationFromPhpFile(
		array $aliases,
		string $filename,
		bool $aliasAsKey = false,
		bool $throws = false
	): void {
		$path = __DIR__ . "/Resource/$filename.php";

		if ( $throws ) {
			$this->expectException( TypeError::class );
			$this->expectExceptionMessage( $path );
		}

		$cards = CardFactory::createFromPhpFile( $path );

		$this->assertCreatedCardAliasesMatch( (array) $cards, $aliases, $aliasAsKey );
		$this->assertAllCardsAreRegistered( (array) $cards );
	}

	/**
	 * @param string[] $aliases
	 * @dataProvider providePhpFiles
	 */
	public function testLazyCardCreationFromPhpFile(
		array $aliases,
		string $filename,
		bool $aliasAsKey = false,
		bool $throws = false
	): void {
		$path = __DIR__ . "/Resource/$filename.php";

		if ( $throws ) {
			$this->expectException( TypeError::class );
			$this->expectExceptionMessage( $path );
		}

		$cards = CardFactory::createFromPhpFile( $path, lazyload: true );

		while ( $cards->valid() ) {
			$alias = $cards->current()->getAlias();
			$key   = $aliasAsKey ? array_search( $cards->key(), $aliases ) : $cards->key();

			$this->assertSame( expected: $aliases[ $key ], actual: $alias );

			$cards->next();
		}
	}

	/** @return mixed[] */
	public function providePhpFiles(): array {
		return array(
			array( array( 'napas' ), 'PhpArray' ),
			array( array( 'napas', 'humo' ), 'PhpCallable' ),
			array( array( 'napas', 'gpn', 'humo' ), 'PhpInvocable', true ),
			array( array(), 'PhpArrayInvalid', false, true ),
		);
	}

	/**
	 * @param array<string|int,Card> $cards
	 * @param string[]               $aliases
	 */
	private function assertCreatedCardAliasesMatch( array $cards, array $aliases, bool $asKey = false ): void {
		foreach ( $aliases as $key => $alias ) {
			$this->assertSame( $alias, actual: $cards[ $asKey ? $alias : $key ]->getAlias() );
		}
	}

	/** @param array<string|int,Card> $cards */
	private function assertAllCardsAreRegistered( array $cards ): void {
		foreach ( $cards as $card ) {
			$reflection = new ReflectionClass( $card );

			$this->assertRegisteredCardType( $card );
			$this->assertInstanceIsConcreteOrAnonymous( $reflection, $card );
		}
	}

	private function assertRegisteredCardType( Card $card ): void {
		$this->assertSame(
			expected: ( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			actual: $card->getType()
		);
	}

	/** @param ReflectionClass<Card> $reflection */
	private function assertInstanceIsConcreteOrAnonymous( ReflectionClass $reflection, Card $card ): void {
		if ( 'napas' !== $card->getAlias() ) {
			$this->assertTrue( $reflection->isAnonymous() );

			return;
		}

		$this->assertFalse( $reflection->isAnonymous() );
		$this->assertInstanceOf( NapasCard::class, actual: $card );
	}
}
