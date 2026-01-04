<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use TypeError;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\CardType;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Test\Resource\NapasCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

class CardFactoryTest extends TestCase {
	public function testGlobalCardClassSetterResetter(): void {
		CardFactory::setGlobalCardClass( NapasCard::class );

		$payload = [
			[
				'name'       => 'Test Card',
				'alias'      => 'test-card',
				'breakpoint' => [ 4, 8, 12 ],
				'code'       => [ 'CVC', 3 ],
				'length'     => [ 16, 19 ],
				'idRange'    => [ 9704 ],
			],
			[
				'name'       => 'Another',
				'alias'      => 'another',
				'breakpoint' => [ 4, 8, 12 ],
				'code'       => [ 'CVV', 3 ],
				'length'     => [ 16 ],
				'idRange'    => [ 9860 ],
			],
		];

		foreach ( ( new CardFactory( $payload ) )->lazyLoadCards() as $card ) {
			$this->assertInstanceOf( NapasCard::class, actual: $card );
		}

		CardFactory::resetGlobalCardClass();
	}

	#[Test]
	#[DataProvider( 'provideNonResolvablePayload' )]
	public function itThrowsExceptionIfNonResolvablePayloadProvided( string|array|null $payload ): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( CardFactory::NON_RESOLVABLE_PAYLOAD );

		( new CardFactory( $payload ) )->createCard();
	}

	public static function provideNonResolvablePayload(): array {
		return [
			[ null ],
			[ '' ],
			[ [] ],
			[ 'invalid/payload/path' ],
		];
	}

	public function testCardCreationFromArray(): void {
		$napas = [
			'name'       => 'Napas',
			'alias'      => 'napas',
			'classname'  => NapasCard::class,
			'breakpoint' => [ 4, 8, 12 ],
			'code'       => [ 'CVC', 3 ],
			'length'     => [ 16, 19 ],
			'idRange'    => [ 9704 ],
		];

		$payload = [
			$napas,
			[
				'name'       => 'Gerbang Pembayaran Nasional',
				'alias'      => 'gpn',
				'type'       => 'Debit Card',
				'breakpoint' => [ 4, 8, 12 ],
				'code'       => [ 'CVC', 3 ],
				'length'     => [ 16, 18, 19 ],
				'idRange'    => [ 1946, 50, 56, 58, [ 60, 63 ] ],
			],
			[
				'name'       => 'Humo',
				'alias'      => 'humo',
				'breakpoint' => [ 4, 8, 12 ],
				'code'       => [ 'CVV', 3 ],
				'length'     => [ 16 ],
				'idRange'    => [ 9860 ],
			],
		];

		$factory = new CardFactory( $payload );
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
			expected: [ 'napas', 'gpn', 'humo' ],
			actual: array_map( static fn( $c ) => $c->getAlias(), array: $cards )
		);

		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $napas ) )->createCard() );
		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $payload ) )->createCard() );
		$this->assertSame( 'humo', actual: ( $humo = ( new CardFactory( $payload ) )->createCard( 2 ) )->getAlias() );
		$this->assertInstanceOf( CardType::class, $humo );
	}

	public function testCardCreationFromJsonFile(): void {
		$path    = __DIR__ . '/Resource/Cards.json';
		$cards   = CardFactory::createFromFile( $path );
		$aliases = [ 'napas', 'gpn', 'humo' ];

		$this->assertCount( expectedCount: 3, haystack: $cards );
		$this->assertCreatedCardAliasesMatch( (array) $cards, $aliases );
		$this->assertAllCardsAreRegistered( (array) $cards );

		$cards = CardFactory::createFromFile( $path, lazyload: true );

		while ( $cards->valid() ) {
			$this->assertSame( expected: $aliases[ $cards->key() ], actual: $cards->current()->getAlias() );
			$cards->next();
		}

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		CardFactory::createFromFile( $path );
	}

	#[DataProvider( 'providePhpFiles' )]
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

		$cards = CardFactory::createFromFile( $path );

		$this->assertCreatedCardAliasesMatch( (array) $cards, $aliases, $aliasAsKey );
		$this->assertAllCardsAreRegistered( (array) $cards );
	}

	#[DataProvider( 'providePhpFiles' )]
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

		$cards = CardFactory::createFromFile( $path, lazyload: true );

		while ( $cards->valid() ) {
			$alias = $cards->current()->getAlias();
			$key   = $aliasAsKey ? array_search( $cards->key(), $aliases, true ) : $cards->key();

			$this->assertSame( expected: $aliases[ $key ], actual: $alias );

			$cards->next();
		}
	}

	public static function providePhpFiles(): array {
		return [
			[ [ 'napas' ], 'PhpArray' ],
			[ [ 'napas', 'humo' ], 'PhpCallable' ],
			[ [ 'napas', 'gpn', 'humo' ], 'PhpInvocable', true ],
			[ [], 'PhpArrayInvalid', false, true ],
		];
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
			$this->assertRegisteredCardType( $card );
			$this->assertInstanceIsProvidedOrDefault( $card );
		}
	}

	private function assertRegisteredCardType( Card $card ): void {
		$this->assertSame(
			expected: ( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			actual: $card->getType()
		);
	}

	private function assertInstanceIsProvidedOrDefault( Card $card ): void {
		if ( 'napas' !== $card->getAlias() ) {
			$this->assertInstanceOf( CardType::class, $card );

			return;
		}

		$this->assertInstanceOf( NapasCard::class, actual: $card );
	}
}
