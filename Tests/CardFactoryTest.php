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

		$schema = array(
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
				'code'       => array( 'CVv', 3 ),
				'length'     => array( 16 ),
				'idRange'    => array( 9860 ),
			),
		);

		$factory = new CardFactory( data: $schema );
		$loader  = $factory->yieldCard();

		$this->assertSame( expected: 'napas', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'gpn', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'humo', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertNull( $loader->current() );

		$cards = ( new CardFactory( $schema ) )->createCards();

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
		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $schema ) )->createCard() );
		$this->assertSame( 'humo', actual: ( new CardFactory( $schema ) )->createCard( 2 )->getAlias() );
	}

	public function testCardCreationFromJsonFile(): void {
		$cards = CardFactory::createFromJsonFile( path: __DIR__ . '/Resource/Cards.json' );

		$this->assertCount( expectedCount: 3, haystack: $cards );
		$this->assertCreatedCardAliasesMatch( $cards, aliases: array( 'napas', 'gpn', 'humo' ) );
		$this->assertAllCardsAreRegistered( $cards );

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		CardFactory::createFromJsonFile( $path );
	}

	/**
	 * @param string[] $aliases
	 * @dataProvider providePhpFiles
	 */
	public function testCardCreationFromPhpFile( array $aliases, string $filename, bool $throws = false ): void {
		$path = __DIR__ . "/Resource/$filename.php";

		if ( $throws ) {
			$this->expectException( TypeError::class );
			$this->expectExceptionMessage( $path );
		}

		$cards = CardFactory::createFromPhpFile( $path );

		$this->assertCreatedCardAliasesMatch( $cards, $aliases );
		$this->assertAllCardsAreRegistered( $cards );
	}

	/** @return mixed[] */
	public function providePhpFiles(): array {
		return array(
			array( array( 'napas' ), 'PhpArray' ),
			array( array( 'napas', 'humo' ), 'PhpCallable' ),
			array( array( 'napas', 'gpn', 'humo' ), 'PhpInvocable' ),
			array( array(), 'PhpArrayInvalid', true ),
		);
	}

	/**
	 * @param array<int,Card> $cards
	 * @param string[]        $aliases
	 */
	private function assertCreatedCardAliasesMatch( array $cards, array $aliases ): void {
		foreach ( $aliases as $key => $alias ) {
			$this->assertSame( $alias, actual: $cards[ $key ]->getAlias() );
		}
	}

	/** @param array<int,Card> $cards */
	private function assertAllCardsAreRegistered( array $cards ): void {
		foreach ( $cards as $card ) {
			$reflection = new ReflectionClass( $card );

			$this->assertRegisteredCardType( $reflection, $card );
			$this->assertInstanceIsConcreteOrAnonymous( $reflection, $card );
		}
	}

	/** @param ReflectionClass<Card> $reflection */
	private function assertRegisteredCardType( ReflectionClass $reflection, Card $card ): void {
		$method = $reflection->getMethod( name: 'getType' );

		$method->setAccessible( true );

		$this->assertSame(
			expected: ( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			actual: $method->invoke( $card )
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
