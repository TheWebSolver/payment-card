<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use TypeError;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Test\Fixture\NapasCard;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardType;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\PaymentCard;

class PaymentCardFactoryTest extends TestCase {
	#[Test]
	public function itEnsuresGlobalCardClassSetterResetterWorks(): void {
		PaymentCardFactory::setGlobalCardClass( NapasCard::class );

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

		foreach ( ( new PaymentCardFactory( $payload ) )->lazyLoad() as $card ) {
			$this->assertInstanceOf( NapasCard::class, actual: $card );
		}

		PaymentCardFactory::resetGlobalCardClass();
	}

	#[Test]
	#[DataProvider( 'provideNonResolvablePayload' )]
	public function itThrowsExceptionIfNonResolvablePayloadProvided( string|array $payload ): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( PaymentCardFactory::NON_RESOLVABLE_PAYLOAD );

		( new PaymentCardFactory( $payload ) )->create( 0 );
	}

	public static function provideNonResolvablePayload(): array {
		return [
			[ '' ],
			[ [] ],
			[ 'invalid/payload/path' ],
		];
	}

	#[Test]
	public function itEnsuresCardsAreCreatedFromPHPArray(): void {
		$payload = [
			[
				'name'       => 'Napas',
				'alias'      => 'napas',
				'classname'  => NapasCard::class,
				'breakpoint' => [ 4, 8, 12 ],
				'code'       => [ 'CVC', 3 ],
				'length'     => [ 16, 19 ],
				'idRange'    => [ 9704 ],
			],
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

		$factory = new PaymentCardFactory( $payload );
		$loader  = $factory->lazyLoad();

		$this->assertSame( expected: 'napas', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'gpn', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( expected: 'humo', actual: $loader->current()->getAlias() );
		$loader->next();
		$this->assertFalse( $loader->valid() );

		$this->assertInstanceOf( NapasCard::class, actual: ( new PaymentCardFactory( $payload ) )->create( 0 ) );
		$this->assertSame( 'humo', actual: ( $humo = ( new PaymentCardFactory( $payload ) )->create( 2 ) )->getAlias() );
		$this->assertInstanceOf( PaymentCardType::class, $humo );

		$this->expectExceptionMessage( sprintf( PaymentCardFactory::UNDEFINED_PAYLOAD_INDEX, 3 ) );
		( new PaymentCardFactory( $payload ) )->create( 3 );
	}

	#[Test]
	public function itEnsuresCardsAreCreatedFromJsonFile(): void {
		$path    = __DIR__ . '/Resource/Cards.json';
		$aliases = [ 'napas', 'gpn', 'humo' ];

		$cards = PaymentCardFactory::createFromFile( $path )->lazyload();

		while ( $cards->valid() ) {
			$this->assertSame( expected: $aliases[ $cards->key() ], actual: $cards->current()->getAlias() );
			$cards->next();
		}

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		PaymentCardFactory::createFromFile( $path )->lazyload()->current();
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

		$cards = PaymentCardFactory::createFromFile( $path )->lazyload();
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

	private function assertRegisteredCardType( PaymentCard $card ): void {
		$this->assertSame(
			( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			$card->getType()
		);
	}

	private function assertInstanceIsProvidedOrDefault( PaymentCard $card ): void {
		if ( 'napas' !== $card->getAlias() ) {
			$this->assertInstanceOf( PaymentCardType::class, $card );

			return;
		}

		$this->assertInstanceOf( NapasCard::class, actual: $card );
	}
}
