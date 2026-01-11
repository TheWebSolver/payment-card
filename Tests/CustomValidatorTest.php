<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\CardType;
use TheWebSolver\Codegarage\Test\Fixture\Validator;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Test\Resource\NapasCard;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;

class CustomValidatorTest extends TestCase {
	public const DOMESTIC_CARDS      = __DIR__ . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . 'Cards.json';
	public const INTERNATIONAL_CARDS = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resource' . DIRECTORY_SEPARATOR . 'paymentCards.json';

	#[Test]
	public function itValidatesWithMockedLuhnAlgorithm(): void {
		$luhnAlwaysPass = new class() extends CardType {
			public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
				return true;
			}
		};

		$americanExpressCard = ( new $luhnAlwaysPass() )
			->setLength( [ 15 ] )
			->setIdRange( [ 34, 37 ] );

		$this->assertTrue( $americanExpressCard->isNumberValid( 378282246310005 ) );

		$luhnAlwaysFails = new class() extends CardType {
			public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
				return false;
			}
		};

		$americanExpressCard = ( new $luhnAlwaysFails() )
			->setLength( [ 15 ] )
			->setIdRange( [ 34, 37 ] );

		$this->assertFalse( $americanExpressCard->isNumberValid( 378282246310005 ) );
	}

	#[Test]
	public function itEnsuresCardsAreResolvedBasedOnExitStatus(): void {
		$factoryOne = new CardFactory(
			[
				[
					'name'       => 'MastercardOne',
					'alias'      => 'mastercard-one',
					'classname'  => NapasCard::class,
					'code'       => [ 'CVC', 3 ],
					'breakpoint' => [ 4, 10 ],
					'length'     => [ 15 ],
					'idRange'    => [ 34, 37 ],
				],
				[
					'name'       => 'MastercardTwo',
					'alias'      => 'mastercard-two',
					'code'       => [ 'CVC', 3 ],
					'breakpoint' => [ 4, 10 ],
					'length'     => [ 15 ],
					'idRange'    => [ 34, 37 ],
				],
			]
		);

		$factoryTwo = new CardFactory(
			[
				[
					'name'       => 'MastercardThree',
					'alias'      => 'mastercard-three',
					'code'       => [ 'CVC', 3 ],
					'breakpoint' => [ 4, 10 ],
					'length'     => [ 15 ],
					'idRange'    => [ 34, 39 ],
				],
				[
					'name'       => 'MastercardFour',
					'alias'      => 'mastercard-four',
					'classname'  => NapasCard::class,
					'code'       => [ 'CVC', 3 ],
					'breakpoint' => [ 4, 10 ],
					'length'     => [ 15 ],
					'idRange'    => [ 34, 37 ],
				],
			]
		);

		$factoryStub     = $this->createStub( CardFactory::class );
		$validator       = new Validator( $factoryStub, $factoryStub );
		$factoryOneCards = $validator->resolve( 378282246310005, $factoryOne, exitOnResolve: false ) ?? [];
		$factoryTwoCards = $validator->resolve( 378282246310005, $factoryTwo, exitOnResolve: false ) ?? [];

		$this->assertCount( 2, $factoryOneCards, 'Both cards have valid ID Range' );
		$this->assertSame( 'MastercardOne', $factoryOneCards[0]->getName() );
		$this->assertSame( 'MastercardTwo', $factoryOneCards[1]->getName() );

		$this->assertCount( 1, $factoryTwoCards, 'Only MastercardFour has valid ID Range.' );
		$this->assertSame( 'MastercardFour', $factoryTwoCards[0]->getName() );

		$validator      = new Validator( $factoryStub, $factoryStub );
		$factoryOneCard = $validator->resolve( 378282246310005, $factoryOne, exitOnResolve: true );

		$this->assertSame( 'MastercardOne', $factoryOneCard?->getName() );
	}

	#[Test]
	#[DataProvider( 'provideCardNumberAndResolvedIndices' )]
	public function itValidatesCardTypesFromPayload( string|int $cardNumber, int $expectedCoveredCards, bool $expectedStatus = true ): void {
		$validator = new Validator( new CardFactory( self::DOMESTIC_CARDS ), new CardFactory( self::INTERNATIONAL_CARDS ) );

		$this->assertSame( $expectedStatus, $validator->validate( $cardNumber ) );
		$this->assertCount( $expectedCoveredCards, $validator->getCoveredCardStatus() );
	}

	/** @return array{string|int,int}[] */
	public static function provideCardNumberAndResolvedIndices(): array {
		return [
			[ 378282246310005, 4 ], // American Express.
			[ '5105105105105100', 6 ], // Mastercard.
			[ 'invalid card number', 13, false ],
		];
	}

	#[Test]
	public function itValidatesCardTypesFromPayloadWithAllowedIndices(): void {
		$validator = new Validator(
			new CardFactory( self::DOMESTIC_CARDS, indicesToCreate: [ 0 ] ),
			new CardFactory( self::INTERNATIONAL_CARDS, indicesToCreate: [ 'americanExpress', 'mastercard' ] )
		);

		$this->assertTrue( $validator->validate( 5105105105105100 ) ); // Mastercard.

		// phpcs:disable Universal.Arrays.MixedArrayKeyTypes.StringKey
		$this->assertSame(
			[
				0                 => Status::Failure,
				1                 => Status::Omitted,
				2                 => Status::Omitted,
				'americanExpress' => Status::Failure,
				'dinersClub'      => Status::Omitted,
				'mastercard'      => Status::Success,
			],
			$validator->getCoveredCardStatus(),
		);
		// phpcs:enable
	}
}
