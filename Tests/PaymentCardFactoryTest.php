<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use TypeError;
use RuntimeException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\Test\Fixture\CreditCard;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\PaymentCardType;

class PaymentCardFactoryTest extends TestCase {
	#[Test]
	public function itEnsuresGlobalCardClassSetterResetterWorks(): void {
		PaymentCardFactory::setGlobalCardClass( CreditCard::class );

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
			$this->assertInstanceOf( CreditCard::class, $card );
		}

		PaymentCardFactory::resetGlobalCardClass();
	}

	/** @param string|mixed[] $payload */
	#[Test]
	#[DataProvider( 'provideNonResolvablePayload' )]
	public function itThrowsExceptionIfNonResolvablePayloadProvided( string|array $payload ): void {
		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( PaymentCardFactory::NON_RESOLVABLE_PAYLOAD );

		( new PaymentCardFactory( $payload ) )->create( 0 );
	}

	/** @return mixed[] */
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
				'classname'  => CreditCard::class,
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

		$this->assertSame( 'napas', $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( 'gpn', $loader->current()->getAlias() );
		$loader->next();
		$this->assertSame( 'humo', $loader->current()->getAlias() );
		$loader->next();
		$this->assertFalse( $loader->valid() );

		$this->assertInstanceOf( CreditCard::class, ( new PaymentCardFactory( $payload ) )->create( 0 ) );
		$this->assertSame( 'humo', ( $humo = ( new PaymentCardFactory( $payload ) )->create( 2 ) )->getAlias() );
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
			$this->assertSame( $aliases[ $cards->key() ], $cards->current()->getAlias() );
			$cards->next();
		}

		$this->expectException( TypeError::class );
		$this->expectExceptionMessage( $path = __DIR__ . '/Resource/CardsInvalid.json' );

		PaymentCardFactory::createFromFile( $path )->lazyload()->current();
	}

	/** @param array<mixed> $aliases */
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

			assert( ! is_null( $card ) );

			$this->assertSame( $expectedAlias, $card->getAlias() );
			$this->assertSame( $expectedIndex, $cards->key() );
			$this->assertRegisteredCardType( $card );
			$this->assertInstanceIsProvidedOrDefault( $card );

			$cards->next();

			++$index;
		}
	}

	/** @return mixed[] */
	public static function providePhpFiles(): array {
		return [
			[ [ 'napas' ], 'PhpArray' ],
			[ [ 'napas', 'humo' ], 'PhpCallable' ],
			[ [ 'napas', 'gpn', 'humo' ], 'PhpInvocable', true ],
			[ [], 'PhpArrayInvalid', false, true ],
		];
	}

	private function assertRegisteredCardType( PaymentCardType $card ): void {
		$this->assertSame(
			( 'gpn' === $card->getAlias() ? 'Debit' : 'Credit' ) . ' Card',
			$card->getType()
		);
	}

	private function assertInstanceIsProvidedOrDefault( PaymentCardType $card ): void {
		if ( 'napas' !== $card->getAlias() ) {
			$this->assertInstanceOf( PaymentCard::class, $card );

			return;
		}

		$this->assertInstanceOf( CreditCard::class, $card );
	}
}
