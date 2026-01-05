<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Fixture;

abstract class Formatter {
	/** @return array{list<string|int>,string|int,string}[] */
	public static function provideCardNumberWithBreakpoints(): array {
		return [
			[ [ 5, 10, 13 ], 1234567891012345, '12345 67891 012 345' ],
			[ [ 3, 9 ], '123456789101998', '123 456789 101998' ],
			[ [ 4, '8', 12 ], 123456789101, '1234 5678 9101' ],
			[ [ 2, 4, 6 ], 123456, '12 34 56' ],
			[ [ 3, 6, '8', 12, '14' ], 3336669991005557777, '333 666 99 9100 55 57777' ],
		];
	}

	abstract public function format( string|int $cardNumber ): string;
	abstract public function setBreakpoint( string|int $number, int ...$numbers ): static;
	/** @return list<int> */
	abstract public function getBreakpoint(): array;
}
