<?php
/**
 * Validator Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;

class ValidatorTest extends TestCase {
	/**
	 * @param mixed[] $code
	 * @dataProvider provideCodes
	 */
	public function testCodeIsValid( array $code, mixed $subject, bool $expected ): void {
		$class = new class( $code ) {
			use Validator;

			/** @param mixed[] $code */
			public function __construct( private readonly array $code ) {}

			/** @return (string|int|(string|int)[])[] */
			public function getLength(): array {
				return array();
			}

			/** @return (string|int|(string|int)[])[] */
			public function getPattern(): array {
				return array();
			}

			/** @return mixed[] */
			public function getCode(): array {
				return $this->code;
			}
		};

		$this->assertSame( $expected, $class->isCodeValid( $subject ) );
	}

	/** @return mixed[] */
	public function provideCodes(): array {
		return array(
			array( array( 'Test', 1 ), 5, true ),
			array( array( 'Test', 2 ), '55', true ),
			array( array( 'Test', 1 ), true, false ),
			array( array( 'Test', 4 ), 798, false ),
			array( array( 'Test', 4 ), '7989', true ),
			array( array( 'Test' ), '7989', false ),
			array( array( 'Test', 3 ), '989', true ),
		);
	}

	/**
	 * @param (string|int|(string|int)[])[] $length
	 * @param (string|int|(string|int)[])[] $pattern
	 * @dataProvider provideNumbers
	 */
	public function testNumberIsValid(
		array $length,
		array $pattern,
		mixed $subject,
		bool $status,
		bool $withLuhnAlgorithm = false
	): void {
		$class = new class( $length, $pattern ) {
			use Validator;

			/**
			 * @param (string|int|(string|int)[])[] $length
			 * @param (string|int|(string|int)[])[] $pattern
			 */
			public function __construct( private array $length, private array $pattern ) {}

			/** @return (string|int|(string|int)[])[] */
			public function getLength(): array {
				return $this->length;
			}

			/** @return (string|int|(string|int)[])[] */
			public function getPattern(): array {
				return $this->pattern;
			}

			/** @return mixed[] */
			public function getCode(): array {
				return array();
			}
		};

		$this->assertSame(
			expected: $status,
			actual: $class->isNumberValid( $subject, $withLuhnAlgorithm )
		);
	}

	/** @return mixed[] */
	public function provideNumbers(): array {
		return array(
			array( array( 12, 14 ), array( 432 ), 432187659876, true ),
			array( array( 12, 14 ), array( true ), 432187659876, false ),
			array( array( true, 14 ), array( 432 ), 432187659876, false ),
			array( array( 12, 14 ), array( 432 ), fn() => 432187659876, false ),
			array( array( 12, 14 ), array( 432 ), '43218765987699', true ),
			array( array( 12, 14 ), array( 432 ), 43218765987, false ),
			array( array( array( 13, 15 ) ), array( 432 ), 43218765987, false ),
			array( array( array( 13, 15 ) ), array( 432 ), 43218765987699, true ),
			array( array( array( 13, 15 ) ), array( 433 ), 43218765987699, false ),
			array( array( array( 15, 13 ) ), array( 432 ), 43218765987699, false ),
			array( array( array( 15 ) ), array( 432 ), 43218765987699, false ),
			array( array( 6, array( 9, 14 ), 17 ), array( 432 ), 43218765987699, true ),
			array( array( 6, array( 9, 13 ), 17 ), array( 432 ), 43218765987699, false ),
			array( array( 6, array( 9, 14 ), 17 ), array( 432, array( 55, 59 ), 71 ), 56218765987699, true ),
			array( array( 6, array( 9, 14 ), 17 ), array( 432, array( 55, 59 ), 71 ), 71218765987699, true ),

		);
	}
}
