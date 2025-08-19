<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Asserter;

class AsserterTest extends TestCase {
	public function testWithoutUsingCardType(): void {
		$this->expectExceptionMessage( 'Payment Card "123"' );
		Asserter::formattingFailed( '123' );
	}

	public function testUsingCardType(): void {
		$card = new Asserter();
		$card->setType( 'Debit Card' );

		$this->expectExceptionMessage( 'Debit Card "123"' );
		Asserter::formattingFailed( '123' );
	}

	#[DataProvider( 'provideResolvingSizes' )]
	public function testResolveSize( array $expected, array $value, string $type, string $errorMsg = '' ): void {
		if ( $errorMsg ) {
			$this->expectExceptionMessage( $errorMsg );
		}

		$this->assertSame( $expected, actual: ( new Asserter() )->assertSizeWith( $value, $type ) );
	}

	public static function provideResolvingSizes(): array {
		return [
			[ [ 1 ], [ '1' ], 'Test' ],
			[ [ 1, 5 ], [ 1, 5 ], 'Test' ],
			[ [ 12, [ 13, 15 ], 20 ], [ '12', [ '13', 15 ], 20 ], 'Test' ],
			[ [ 1 ], [], 'Test1', 'Test1 must have atleast one element.' ],
			[ [ 1 ], [ -1 ], 'Test2', 'Test2 minimum value must be a positive integer.' ],
			[ [ 0 ], [ [ 5, 5 ] ], 'Test3', 'Test3 minimum value must be less than maximum value.' ],
			[ [ 0 ], [ [ 5, 4 ] ], 'Test3', 'Test3 minimum value must be less than maximum value.' ],
			[ [ 0 ], [ [ 1, 2, 3 ] ], 'Test4', 'Test4 value must only be of two elements in an array.' ],
			[ [ 0 ], [ 1, false ], 'Test5', 'Test5 must be between [0-9] as either a "string" or an "int" type. "bool" type given.' ],
		];
	}

	public function testNormalize(): void {
		$this->assertSame( '12345', Asserter::normalize( 'Invalid-123@45.but#normal!ed' ) );
	}

	public function testParseName(): void {
		$this->assertSame( expected: 'some', actual: Asserter::parsePropNameFrom( 'getSome' ) );
		$this->assertSame( expected: 'some', actual: Asserter::parsePropNameFrom( 'setSome' ) );
		$this->assertSame( expected: 'ome', actual: Asserter::parsePropNameFrom( 'doSome' ) );
	}
}
