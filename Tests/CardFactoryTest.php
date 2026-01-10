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
	#[Test]
	public function itEnsuresGlobalCardClassSetterResetterWorks(): void {
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

	#[Test]
	public function itEnsuresCardsAreCreatedFromPHPArray(): void {
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
		$this->assertFalse( $loader->valid() );

		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $napas ) )->createCard() );
		$this->assertInstanceOf( NapasCard::class, actual: ( new CardFactory( $payload ) )->createCard() );
		$this->assertSame( 'humo', actual: ( $humo = ( new CardFactory( $payload ) )->createCard( 2 ) )->getAlias() );
		$this->assertInstanceOf( CardType::class, $humo );
	}

	#[Test]
	public function itEnsuresCardsAreCreatedFromJsonFile(): void {
		$path    = __DIR__ . '/Resource/Cards.json';
		$aliases = [ 'napas', 'gpn', 'humo' ];

		$cards = CardFactory::createFromFile( $path );

		while ( $cards->valid() ) {
			$this->assertSame( expected: $aliases[ $cards->key() ], actual: $cards->current()->getAlias() );
			$cards->next();
		}

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		CardFactory::createFromFile( $path )->current();
	}

	#[Test]
	#[DataProvider( 'providePhpFiles' )]
	public function itEnsuresCardsAreCreatedFromPHPFile(
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
		$index = 0;

		while ( $cards->valid() ) {
			$card          = $cards->current();
			$expectedAlias = $aliases[ $index ];
			$expectedIndex = $aliasAsKey ? $expectedAlias : $index;

			$this->assertSame( $expectedAlias, actual: $card->getAlias() );
			$this->assertSame( $expectedIndex, $cards->key() );
			$this->assertRegisteredCardType( $card );
			$this->assertInstanceIsProvidedOrDefault( $card );

			$cards->next();

			++$index;
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

	private function assertRegisteredCardType( Card $card ): void {
		$this->assertSame(
			( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			$card->getType()
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
