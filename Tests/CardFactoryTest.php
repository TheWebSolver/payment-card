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
	public function testCardCreationFromJsonFile(): void {
		$cards = CardFactory::createFromJsonFile( path: __DIR__ . '/Resource/Cards.json' );

		$this->assertCount( expectedCount: 3, haystack: $cards );
		$this->assertAllCardsAreRegistered( $cards, aliases: array( 'napas', 'gpn', 'humo' ) );

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

		$this->assertAllCardsAreRegistered( CardFactory::createFromPhpFile( $path ), $aliases );
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
	 * @param array<string,Card> $cards
	 * @param string[]           $aliases
	 */
	private function assertAllCardsAreRegistered( array $cards, array $aliases ): void {
		foreach ( $aliases as $alias ) {
			$card       = $cards[ $alias ];
			$reflection = new ReflectionClass( $card );

			$this->assertSame( expected: $alias, actual: $card->getAlias() );
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
