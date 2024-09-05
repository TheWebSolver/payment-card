<?php
/**
 * Card Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\Asserter;

class AsserterTest extends TestCase {
	public function testWithoutUsingCardType(): void {
		$this->expectExceptionMessage( sprintf( Asserter::INVALID_FORMATTING, 'Payment Card', '123' ) );
		Asserter::formattingFailed( '123' );
	}

	public function testUsingCardType(): void {
		$card = new Asserter();
		$card->setType( Asserter::DEBIT );

		$this->expectExceptionMessage( sprintf( Asserter::INVALID_FORMATTING, 'Debit Card', '123' ) );
		Asserter::formattingFailed( '123' );
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

		$this->assertSame( $expected, actual: ( new Asserter() )->assertSizeWith( $value, $type ) );
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
		$this->assertSame( '12345', Asserter::normalize( 'Invalid-123@45.but#normal!ed' ) );
	}

	public function testParseName(): void {
		$this->assertSame( expected: 'some', actual: Asserter::parsePropNameFrom( 'getSome' ) );
		$this->assertSame( expected: 'some', actual: Asserter::parsePropNameFrom( 'setSome' ) );
		$this->assertSame( expected: 'ome', actual: Asserter::parsePropNameFrom( 'doSome' ) );
	}

	public function testSome(): void {
		// Asserter::isProcessing( 'test1' );
		Asserter::isProcessing( 'test3' );

		$a = ( new Asserter() );
		$a->assertSizeWith( array( array( 11, 1 ) ), 'test2' );

		Asserter::assertionFailed( Asserter::NEEDS_ONE_ELEMENT );
	}
}
