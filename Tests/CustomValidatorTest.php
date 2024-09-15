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
use TheWebSolver\Codegarage\PaymentCard\Traits\BatchResolver;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;
use TheWebSolver\Codegarage\PaymentCard\CardFactory as Factory;

class CustomValidatorTest extends TestCase {
	public static string $payload;

	public static function setUpBeforeClass(): void {
		$slash         = DIRECTORY_SEPARATOR;
		self::$payload = dirname( __DIR__ ) . $slash . 'Resource' . $slash . 'paymentCards.json';
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
		$allowedCards = array( 'americanExpress', 'dinersClub', 'visa' );
		$class        = new class( $allowedCards ) {
			use CardResolver {
				getCards as public;
			}

			/** @param ?string[] $allowedCards */
			public function __construct( ?array $allowedCards = null, Factory $factory = new Factory() ) {
				if ( empty( $allowedCards ) ) {
					return;
				}

				$this->withoutDefaults()->setCards(
					...array_map( $factory->withPayload( CustomValidatorTest::$payload )->createCard( ... ), $allowedCards )
				);
			}

			public function validate( string|int $cardNumber ): bool {
				return $this->resolveCardFromNumber( $cardNumber ) instanceof Card;
			}
		};

		$this->assertCount( expectedCount: 3, haystack: $class->getCards() );
		$this->assertTrue( $class->validate( cardNumber: 378282246310005 ) );   // American Express.
		$this->assertFalse( $class->validate( cardNumber: 5105105105105100 ) ); // Mastercard
	}

	public function testInBatch(): void {
		$validator = new class() {
			use BatchResolver {
				getCoveredCards as public;
				resetCoveredCards as public;
			}

			/** @var array{first:string,second:string} */
			private array $batches;

			public function __construct() {
				$slash         = DIRECTORY_SEPARATOR;
				$this->batches = array(
					'first'  => __DIR__ . $slash . 'Resource' . $slash . 'Cards.json',
					'second' => dirname( __DIR__ ) . $slash . 'Resource' . $slash . 'paymentCards.json',
				);
			}

			public function validate( string|int $cardNumber ): bool {
				$firstBatch = Factory::createFromJsonFile( path: $this->batches['first'], lazyload: true );

				if ( $this->resolveCardFromNumberIn( $firstBatch, $cardNumber ) ) {
					return true;
				}

				$secondBatch = Factory::createFromJsonFile( path: $this->batches['second'], lazyload: true );

				return $this->resolveCardFromNumberIn( $secondBatch, $cardNumber ) ? true : false;
			}
		};

		$this->assertTrue( $validator->validate( 378282246310005 ) ); // American Express.
		$this->assertCount( expectedCount: 4, haystack: $validator->getCoveredCards() );

		$validator->resetCoveredCards();

		$this->assertTrue( $validator->validate( 5105105105105100 ) ); // Mastercard.
		$this->assertCount( expectedCount: 6, haystack: $validator->getCoveredCards() );

		$validator->resetCoveredCards();

		$this->assertFalse( $validator->validate( 'invalid card number' ) );
		$this->assertCount( expectedCount: 13, haystack: $validator->getCoveredCards() );
	}
}
