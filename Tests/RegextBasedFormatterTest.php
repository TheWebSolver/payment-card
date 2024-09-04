<?php
/**
 * Regex based formatter Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\FormatterDataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

class RegexBasedFormatterTest extends TestCase {
	use FormatterDataProvider;

	/**
	 * @param (string|int)[] $gaps
	 * @dataProvider provideVariousNumbersAndGaps
	 */
	public function testNumberFormattingBasedOnGap( array $gaps, string|int $number, string $expected ): void {
		$test1 = new class() {
			use RegexBasedFormatter;
		};

		$test1->setGap( ...$gaps );

		$this->assertSame( expected: array_map( intval( ... ), $gaps ), actual: $test1->getGap() );
		$this->assertSame( $expected, actual: $test1->format( $number ) );
	}
}
