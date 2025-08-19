<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard as Card;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver;

class PaymentCardTest extends TestCase {
	#[DataProvider( 'provideCreditCards' )]
	public function testCreditCards( Card $card, string|int $number ): void {
		$this->assertTrue( $card->isNumberValid( $number ) );
	}

	/**
	 * Only valid ones are selected from different sources. Links provided below.
	 *
	 * @link https://www.paypalobjects.com/en_GB/vhelp/paypalmanager_help/credit_card_numbers.htm
	 * @link https://developer.paypal.com/braintree/docs/guides/unionpay/testing
	 * @link http://support.worldpay.com/support/kb/bg/testandgolive/tgl5103.html
	 * @link https://know.eshopworld.com/space/SUP/808943617/Test+Cards
	 * @link https://docs.connect.worldline-solutions.com/documentation/testcases/detail/troy-debit
	 * @link https://developer.craftgate.io/en/test-cards/all-successful-test-cards/
	 * @link https://cardguru.io/credit-card-generator
	 */
	public static function provideCreditCards(): array {
		return [
			[ Card::AmericanExpress, 378282246310005 ],
			[ Card::AmericanExpress, '371449635398431' ],
			[ Card::AmericanExpress, 378734493671000 ],
			[ Card::DinersClub, 30569309025904 ],
			[ Card::DinersClub, '38520000023237' ],
			[ Card::DinersClub, 36700102000000 ],
			[ Card::DinersClub, '3893872265492575' ],
			[ Card::Mastercard, 5555555555554444 ],
			[ Card::Mastercard, '5105105105105100' ],
			[ Card::Mastercard, 5169320000000008 ],
			[ Card::Discover, 6011111111111117 ],
			[ Card::Discover, '6011000990139424' ],
			[ Card::Discover, 6493505952542224798 ],
			[ Card::UnionPay, 6212345678901265 ],
			[ Card::UnionPay, 6212345678901232 ],
			[ Card::UnionPay, 6212345678900028 ],
			[ Card::UnionPay, 6212345678900036 ],
			[ Card::UnionPay, 6212345678900085 ],
			[ Card::UnionPay, 6212345678900093 ],
			[ Card::UnionPay, '62123456789000003' ],
			[ Card::UnionPay, 621234567890000002 ],
			[ Card::UnionPay, '6212345678900000003' ],
			[ Card::Maestro, 6759649826438453 ],
			[ Card::Maestro, '6767741367886578' ],
			[ Card::Maestro, 6759427031424752 ],
			[ Card::Visa, 4111111111111111 ],
			[ Card::Visa, '4012888888881881' ],
			[ Card::Visa, 4222222222222 ],
			[ Card::Visa, '4917610000000000003' ],
			[ Card::Visa, 4462030000000000 ],
			[ Card::Visa, '4917300800000000' ],
			[ Card::Troy, 9792030000000000 ],
			[ Card::Troy, '9792052565200015' ],
			[ Card::Troy, 9792170000000004 ],
			[ Card::Troy, 9792800000000006 ],
			[ Card::Troy, '6500830000000002' ],
			[ Card::Jcb, 3530111333300000 ],
			[ Card::Jcb, '3566002020360505' ],
			[ Card::Jcb, 353061039963254559 ],
			[ Card::Jcb, '354094481843152463' ],
			[ Card::Mir, 2202779879795392 ],
			[ Card::Mir, '2201240328710764' ],
			[ Card::Mir, 2203757216192209 ],
		];
	}

	public function testCardResolver(): void {
		$class = new class() {
			use CardResolver {
				resolveCardFromNumber as public;
				withoutDefaults as public;
			}
		};

		$range = [ 62212678, 6229258 ];
		$card  = PaymentCard::maybeGetPartneredCard( $range, Card::Discover );

		$this->assertSame( PaymentCard::UnionPay, $card );

		// $card = $class->resolveCardFromNumber( 6500830000000002 );
		// $this->assertSame( 'Troy', $class->resolveCardFromNumber( 6500830000000002 )?->getName() );

		$this->expectException( LogicException::class );
		$class->withoutDefaults()->resolveCardFromNumber( 0 );
	}
}
