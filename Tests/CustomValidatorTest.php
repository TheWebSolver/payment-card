<?php
/**
 * Validator Test with custom implementation.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\CardType;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;
use TheWebSolver\Codegarage\PaymentCard\CardFactory as Factory;

class CustomValidatorTest extends TestCase {
	private static string $cardPayload;

	public static function setUpBeforeClass(): void {
		$slash             = DIRECTORY_SEPARATOR;
		self::$cardPayload = dirname( __DIR__ ) . $slash . 'Resource' . $slash . 'paymentCards.json';
	}

	public function testWithCustomLuhn(): void {
		$luhnAlwaysPass = new class() extends CardType {
			public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
				return true;
			}
		};

		$americanExpressCard = ( new $luhnAlwaysPass() )
			->setLength( array( 15 ) )
			->setIdRange( array( 34, 37 ) );

		$this->assertTrue( $americanExpressCard->isNumberValid( 378282246310005 ) );

		$luhnAlwaysFails = new class() extends CardType {
			public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
				return false;
			}
		};

		$americanExpressCard = ( new $luhnAlwaysFails() )
			->setLength( array( 15 ) )
			->setIdRange( array( 34, 37 ) );

		$this->assertFalse( $americanExpressCard->isNumberValid( 378282246310005 ) );
	}

	public function testWithAllowedCards(): void {
		$payload      = self::$cardPayload;
		$allowedCards = array( 'americanExpress', 'dinersClub', 'visa' );
		$class        = new class( $payload, $allowedCards) {
			use CardResolver {
				getCards as public;
			}

			/** @param string[] $allowedCards */
			public function __construct( string $payload, ?array $allowedCards = null, Factory $factory = new Factory() ) {
				if ( empty( $allowedCards ) ) {
					return;
				}

				$this->withoutDefaults();

				$factory = $factory->withPayload( $payload );
				$cards   = array();

				foreach ( $allowedCards as $key ) {
					$cards[] = $factory->createCard( index: $key );
				}

				$this->setCards( ...$cards );
			}

			public function validate( string|int $cardNumber ): bool {
				return $this->resolveCardFromNumber( (string) $cardNumber ) instanceof Card;
			}
		};

		$this->assertTrue( $class->validate( cardNumber: 378282246310005 ) );   // American Express.
		$this->assertFalse( $class->validate( cardNumber: 5105105105105100 ) ); // Master Card
	}
}
