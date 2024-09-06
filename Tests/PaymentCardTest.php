<?php
/**
 * Payment Card enum Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage;

use LogicException;
use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard as Card;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver;

class PaymentCardTest extends TestCase {
	/** @dataProvider provideCreditCards */
	public function testCreditCards( Card $card, string|int $number ): void {
		$this->assertTrue( $card->isNumberValid( $number ) );
	}

	/**
	 * Only valid ones are selected from different sources. Links provided below.
	 *
	 * @return array<array{0:Card,1:string|int}>
	 * @link https://www.paypalobjects.com/en_GB/vhelp/paypalmanager_help/credit_card_numbers.htm
	 * @link https://developer.paypal.com/braintree/docs/guides/unionpay/testing
	 * @link http://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html
	 * @link https://know.eshopworld.com/space/SUP/808943617/Test+Cards
	 * @link https://docs.connect.worldline-solutions.com/documentation/testcases/detail/troy-debit
	 * @link https://developer.craftgate.io/en/test-cards/all-successful-test-cards/
	 * @link https://cardguru.io/credit-card-generator
	 */
	public function provideCreditCards(): array {
		return array(
			array( Card::AmericanExpress, 378282246310005 ),
			array( Card::AmericanExpress, '371449635398431' ),
			array( Card::AmericanExpress, 378734493671000 ),
			array( Card::DinersClub, 30569309025904 ),
			array( Card::DinersClub, '38520000023237' ),
			array( Card::DinersClub, 36700102000000 ),
			array( Card::DinersClub, '3893872265492575' ),
			array( Card::Mastercard, 5555555555554444 ),
			array( Card::Mastercard, '5105105105105100' ),
			array( Card::Mastercard, 5169320000000008 ),
			array( Card::Discover, 6011111111111117 ),
			array( Card::Discover, '6011000990139424' ),
			array( Card::Discover, 6493505952542224798 ),
			array( Card::UnionPay, 6212345678901265 ),
			array( Card::UnionPay, 6212345678901232 ),
			array( Card::UnionPay, 6212345678900028 ),
			array( Card::UnionPay, 6212345678900036 ),
			array( Card::UnionPay, 6212345678900085 ),
			array( Card::UnionPay, 6212345678900093 ),
			array( Card::UnionPay, '62123456789000003' ),
			array( Card::UnionPay, 621234567890000002 ),
			array( Card::UnionPay, '6212345678900000003' ),
			array( Card::Maestro, 6759649826438453 ),
			array( Card::Maestro, '6767741367886578' ),
			array( Card::Maestro, 6759427031424752 ),
			array( Card::Visa, 4111111111111111 ),
			array( Card::Visa, '4012888888881881' ),
			array( Card::Visa, 4222222222222 ),
			array( Card::Visa, '4917610000000000003' ),
			array( Card::Visa, 4462030000000000 ),
			array( Card::Visa, '4917300800000000' ),
			array( Card::Troy, 9792030000000000 ),
			array( Card::Troy, '9792052565200015' ),
			array( Card::Troy, 9792170000000004 ),
			array( Card::Troy, 9792800000000006 ),
			array( Card::Troy, '6500830000000002' ),
			array( Card::Jcb, 3530111333300000 ),
			array( Card::Jcb, '3566002020360505' ),
			array( Card::Jcb, 353061039963254559 ),
			array( Card::Jcb, '354094481843152463' ),
			array( Card::Mir, 2202779879795392 ),
			array( Card::Mir, '2201240328710764' ),
			array( Card::Mir, 2203757216192209 ),
		);
	}

	public function testCardResolver(): void {
		$class = new class() {
			use CardResolver;
		};

		$range = array( 62212678, 6229258 );
		$card  = PaymentCard::getAltCardFrom( $range, Card::Discover );

		$this->assertSame( PaymentCard::UnionPay->getName(), $card?->getName() );

		// $card = $class->resolveCardFromNumber( 6500830000000002 );
		// $this->assertSame( 'Troy', $class->resolveCardFromNumber( 6500830000000002 )?->getName() );

		$this->expectException( LogicException::class );
		$class->resolveCardFromNumber( 0, registeredOnly: true );
	}
}
