<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\PaymentCard\CardType;
use TheWebSolver\Codegarage\Test\Fixture\Validator;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;

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
	public function itValidatesCardTypesFromPayload(): void {
		$validator = new Validator( new CardFactory( self::DOMESTIC_CARDS ), new CardFactory( self::INTERNATIONAL_CARDS ) );

		$this->assertTrue( $validator->validate( 378282246310005 ) ); // American Express.
		$this->assertCount( 4, $validator->getCoveredCardIndices() );

		$validator->resetCoveredCardIndices();

		$this->assertTrue( $validator->validate( 5105105105105100 ) ); // Mastercard.
		$this->assertCount( 6, $validator->getCoveredCardIndices() );

		$validator->resetCoveredCardIndices();

		$this->assertFalse( $validator->validate( 'invalid card number' ) );
		$this->assertCount( 13, $validator->getCoveredCardIndices() );

		$validator->resetCoveredCardIndices();
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
				0                 => 'invalid',
				1                 => 'disallowed',
				2                 => 'disallowed',
				'americanExpress' => 'invalid',
				'dinersClub'      => 'disallowed',
				'mastercard'      => 'valid',
			],
			$validator->getCoveredCardIndices(),
		);
		// phpcs:enable

		$validator->resetCoveredCardIndices();
	}
}
