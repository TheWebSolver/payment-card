<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;

class CardValidationTest extends TestCase {
	#[DataProvider( 'provideCodes' )]
	public function testCodeIsValid( array $code, mixed $subject, bool $expected ): void {
		$class = new class( $code ) {
			use Validator;

			/** @param mixed[] $code */
			public function __construct( private readonly array $code ) {}

			public function needsLuhnCheck(): bool {
				return true;
			}

			/** @return mixed[] */
			public function getLength(): array {
				return [];
			}

			/** @return mixed[] */
			public function getIdRange(): array {
				return [];
			}

			/** @return mixed[] */
			public function getCode(): array {
				return $this->code;
			}
		};

		$this->assertSame( $expected, $class->isCodeValid( $subject ) );
	}

	public static function provideCodes(): array {
		// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.AssociativeArrayFound
		return [
			[ [ 'name' => 'Test', 'size' => 1 ], 5, true ],
			[ [ 'name' => 'Test', 'size' => 2 ], '55', true ],
			[ [ 'name' => 'Test', 'size' => 1 ], true, false ],
			[ [ 'name' => 'Test', 'size' => 4 ], 798, false ],
			[ [ 'name' => 'Test', 'size' => 4 ], '7989', true ],
			[ [ 'name' => 'Test', 'size' => 3 ], '7989', false ],
			[ [ 'name' => 'Test', 'size' => 3 ], '989', true ],
		];
		// phpcs:enable
	}

	/**
	 * @param (string|int|(string|int)[])[] $length
	 * @param (string|int|(string|int)[])[] $ranges
	 */
	#[DataProvider( 'provideNumbers' )]
	public function testNumberIsValid(
		array $length,
		array $ranges,
		mixed $subject,
		bool $status,
		bool $withLuhnAlgorithm = false
	): void {
		$class = new class( $length, $ranges, $withLuhnAlgorithm ) {
			use Validator;

			/**
			 * @param (string|int|(string|int)[])[] $length
			 * @param (string|int|(string|int)[])[] $ranges
			 */
			public function __construct( private array $length, private array $ranges, private bool $luhn ) {}

			public function needsLuhnCheck(): bool {
				return $this->luhn;
			}

			/** @return (string|int|(string|int)[])[] */
			public function getLength(): array {
				return $this->length;
			}

			/** @return (string|int|(string|int)[])[] */
			public function getIdRange(): array {
				return $this->ranges;
			}

			/** @return mixed[] */
			public function getCode(): array {
				return [];
			}
		};

		$this->assertSame(
			expected: $status,
			actual: $class->isNumberValid( $subject )
		);
	}

	public static function provideNumbers(): array {
		return [
			[ [ 12, 14 ], [ 432 ], 432187659876, true ],
			[ [ 12, 14 ], [ true ], 432187659876, false ],
			[ [ true, 14 ], [ 432 ], 432187659876, false ],
			[ [ 12, 14 ], [ 432 ], fn() => 432187659876, false ],
			[ [ 12, 14 ], [ 432 ], '43218765987699', true ],
			[ [ 12, 14 ], [ 432 ], 43218765987, false ],
			[ [ [ 13, 15 ] ], [ 432 ], 43218765987, false ],
			[ [ [ 13, 15 ] ], [ 432 ], 43218765987699, true ],
			[ [ [ 13, 15 ] ], [ 433 ], 43218765987699, false ],
			[ [ [ 15, 13 ] ], [ 432 ], 43218765987699, false ],
			[ [ [ 15 ] ], [ 432 ], 43218765987699, false ],
			[ [ 6, [ 9, 14 ], 17 ], [ 432 ], 43218765987699, true ],
			[ [ 6, [ 9, 13 ], 17 ], [ 432 ], 43218765987699, false ],
			[ [ 6, [ 9, 14 ], 17 ], [ 432, [ 55, 59 ], 71 ], 56218765987699, true ],
			[ [ 6, [ 9, 14 ], 17 ], [ 432, [ 55, 59 ], 71 ], 71218765987699, true ],

		];
	}
}
