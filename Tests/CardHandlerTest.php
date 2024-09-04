<?php
/**
 * Card Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\CardHandler;

class CardTest extends TestCase {
	public function testWithoutUsingCardType(): void {
		$this->expectExceptionMessage( sprintf( CardHandler::INVALID_FORMATTING, 'Payment Card', '123' ) );
		CardHandler::formattingFailed( '123' );
	}

	public function testUsingCardType(): void {
		$card = new CardHandler();
		$card->setType( CardHandler::DEBIT );

		$this->expectExceptionMessage( sprintf( CardHandler::INVALID_FORMATTING, 'Debit Card', '123' ) );
		CardHandler::formattingFailed( '123' );
	}
}
