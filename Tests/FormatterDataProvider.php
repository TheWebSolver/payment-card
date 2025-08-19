<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\Attributes\DataProvider;

trait FormatterDataProvider {
	abstract protected function classWithTrait(): object;

	#[DataProvider( 'provideVariousNumbersAndBreakpoints' )]
	public function testNumberFormattingBasedOnBreakpoint(
		array $numbers,
		string|int $number,
		string $expected
	): void {
		$class = $this->classWithTrait()->setBreakpoint( ...$numbers ); // @phpstan-ignore-line

		$this->assertSame( expected: array_map( intval( ... ), $numbers ), actual: $class->getBreakpoint() );
		$this->assertSame( $expected, actual: $class->format( $number ) );
	}

	public static function provideVariousNumbersAndBreakpoints(): array {
		return [
			[ [ 5, 10, 13 ], 1234567891012345, '12345 67891 012 345' ],
			[ [ 3, 9 ], '123456789101998', '123 456789 101998' ],
			[ [ 4, '8', 12 ], 123456789101, '1234 5678 9101' ],
			[ [ 2, 4, 6 ], 123456, '12 34 56' ],
			[ [ 3, 6, '8', 12, '14' ], 3336669991005557777, '333 666 99 9100 55 57777' ],
		];
	}
}
