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

class CardHandlerTest extends TestCase {
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

	/**
	 * @param mixed[] $expected
	 * @param mixed[] $value
	 * @dataProvider provideResolvingSizes
	 */
	public function testResolveSize( array $expected, array $value, string $type, string $errorMsg = '' ): void {
		if ( $errorMsg ) {
			$this->expectExceptionMessage( $errorMsg );
		}

		$this->assertSame( $expected, actual: CardHandler::resolveSizeWith( $value, $type ) );
	}

	/** @return mixed[] */
	public function provideResolvingSizes(): array {
		return array(
			array( array( 1 ), array( '1' ), 'Test' ),
			array( array( 1, 5 ), array( 1, 5 ), 'Test' ),
			array( array( 12, array( 13, 15 ), 20 ), array( '12', array( '13', 15 ), 20 ), 'Test' ),
			array( array( 1 ), array(), 'Test1', 'Test1 must have atleast one element.' ),
			array( array( 1 ), array( -1 ), 'Test2', 'Test2 minimum value must be a positive integer.' ),
			array( array( 0 ), array( array( 5, 5 ) ), 'Test3', 'Test3 minimum value must be less than maximum value.' ),
			array( array( 0 ), array( array( 5, 4 ) ), 'Test3', 'Test3 minimum value must be less than maximum value.' ),
			array( array( 0 ), array( array( 1, 2, 3 ) ), 'Test4', 'Test4 value must only be of two elements in an array.' ),
			array( array( 0 ), array( 1, false ), 'Test5', 'Test5 must be between [0-9] as either a "string" or an "int" type. "bool" type given.' ),
		);
	}

	public function testNormalize(): void {
		$this->assertSame( '12345', CardHandler::normalize( 'Invalid-123@45.but#normal!ed' ) );
	}

	public function testParseName(): void {
		$this->assertSame( expected: 'some', actual: CardHandler::parsePropNameFrom( 'getSome' ) );
		$this->assertSame( expected: 'some', actual: CardHandler::parsePropNameFrom( 'setSome' ) );
		$this->assertSame( expected: 'ome', actual: CardHandler::parsePropNameFrom( 'doSome' ) );
	}
}
