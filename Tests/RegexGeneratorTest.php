<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use OutOfBoundsException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexGenerator;

class RegexGeneratorTest extends TestCase {
	/** @param string[] $expected */
	private function performTest( array $expected, int $size, bool $throws, string $type ): void {
		$class = new class( $type, $size ) {
			use RegexGenerator;

			public function __construct( private readonly string $type, private readonly int $size ) {}

			/** @return string[] */
			public function generate(): array {
				return 'alt' !== $this->type ? $this->getDefaultRegex( $this->size ) : $this->getAltRegex( $this->size );
			}
		};

		if ( $throws ) {
			$this->expectException( OutOfBoundsException::class );
		}

		$this->assertSame( $expected, actual: $class->generate() );
	}

	#[DataProvider( 'provideDefaultSizesAndRespectiveGeneration' )]
	public function testGeneratingDefaultRegex( array $expected, int $size, bool $throws = false ): void {
		$this->performTest( $expected, $size, $throws, 'default' );
	}

	public static function provideDefaultSizesAndRespectiveGeneration(): array {
		$holder = '$1 $2 $3';

		return [
			[ [ '/(\d{4})(\d{4})(\d{4})/', $holder ], 12 ],
			[ [ '/(\d{4})(\d{4})(\d{4})(\d{3})/', "$holder $4" ], 15 ],
			[ [ '/(\d{4})(\d{4})(\d{4})/', $holder ], 10, true ],
			[ [ '/(\d{4})(\d{4})(\d{4})(\d{4})(\d{9})/', "$holder $4 $5" ], 25 ],
		];
	}

	#[DataProvider( 'provideAltSizesAndRespectiveGeneration' )]
	public function testGeneratingAltRegex( array $expected, int $size, bool $throws = false ): void {
		$this->performTest( $expected, $size, $throws, 'alt' );
	}

	public static function provideAltSizesAndRespectiveGeneration(): array {
		$holder = '$1 $2 $3';

		return [
			[ [ '/(\d{4})(\d{6})(\d{2})/', $holder ], 12 ],
			[ [ '/(\d{4})(\d{6})(\d{5})/', $holder ], 15 ],
			[ [ '/(\d{4})(\d{6})(\d{1})/', $holder ], 11, true ],
			[ [ '/(\d{4})(\d{6})(\d{9})/', $holder ], 19 ],
			[ [ '/(\d{4})(\d{6})(\d{4})/', $holder ], 14 ],
			[ [ '/(\d{4})(\d{6})(\d{15})/', $holder ], 25 ],
		];
	}
}
