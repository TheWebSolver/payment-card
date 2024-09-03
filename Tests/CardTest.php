<?php
/**
 * Card Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\Card;

class CardTest extends TestCase {
	public function testWithoutUsingCardType(): void {
		$this->expectExceptionMessage( sprintf( Card::INVALID_FORMATTING, 'Payment Card', '123' ) );
		Card::formattingFailed( '123' );
	}

	public function testUsingCardType(): void {
		$card = new Card();
		$card->setCardType( Card::DEBIT );

		$this->expectExceptionMessage( sprintf( Card::INVALID_FORMATTING, 'Debit Card', '123' ) );
		Card::formattingFailed( '123' );
	}
}
