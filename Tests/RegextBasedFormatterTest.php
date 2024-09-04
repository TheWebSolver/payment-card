<?php
/**
 * Regex based formatter Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

class RegexBasedFormatterTest extends TestCase {
	public function testGapSetterGetter(): void {
		$class = new class() {
			use RegexBasedFormatter;
		};

		$class->setGap( 4, 8, '12' );

		$this->assertSame( expected: array( 4, 8, 12 ), actual: $class->getGap() );
	}

	/**
	 * @param (string|int)[] $gaps
	 * @dataProvider provideVariousNumbersAndGaps
	 */
	public function testNumberFormattingBasedOnGap( array $gaps, string|int $number, string $expected ): void {
		$test1 = new class() {
			use RegexBasedFormatter;
		};

		$test1->setGap( ...$gaps );

		$this->assertSame( $expected, actual: $test1->format( $number ) );
	}

	/** @return array<mixed[]> */
	public function provideVariousNumbersAndGaps(): array {
		return array(
			array( array( 5, 10, 13 ), 1234567891012345, '12345 67891 012 345' ),
			array( array( 3, 9 ), '123456789101998', '123 456789 101998' ),
			array( array( 4, '8', 12 ), 123456789101, '1234 5678 9101' ),
			array( array( 2, 4, 6 ), 123456, '12 34 56' ),
			array( array( 3, 6, '8', 12, '15' ), 3336669991005557777, '333 666 99 9100 555 7777' ),
		);
	}
}
