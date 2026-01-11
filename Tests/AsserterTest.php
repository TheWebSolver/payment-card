<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Asserter;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;

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
			[ [ 1 ], [], 'Test1', sprintf( Asserter::NEEDS_ONE_ELEMENT, PaymentCardFactory::DEFAULT_CARD, 'Test1' ) ],
			[ [ 1 ], [ -1 ], 'Test2', sprintf( Asserter::NEEDS_POSITIVE_INT, PaymentCardFactory::DEFAULT_CARD, 'Test2' ) ],
			[ [ 0 ], [ [ 5, 5 ] ], 'Test3', sprintf( Asserter::NEEDS_MIN_LESS_THAN_MAX, PaymentCardFactory::DEFAULT_CARD, 'Test3' ) ],
			[ [ 0 ], [ [ 5, 4 ] ], 'Test3', sprintf( Asserter::NEEDS_MIN_LESS_THAN_MAX, PaymentCardFactory::DEFAULT_CARD, 'Test3' ) ],
			[ [ 0 ], [ [ 1, 2, 3 ] ], 'Test4', sprintf( Asserter::NEEDS_TWO_ELEMENTS, PaymentCardFactory::DEFAULT_CARD, 'Test4' ) ],
			[ [ 0 ], [ 1, false ], 'Test5', sprintf( Asserter::NEEDS_INT_OR_NUMERIC, PaymentCardFactory::DEFAULT_CARD, 'Test5', 'bool' ) ],
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
