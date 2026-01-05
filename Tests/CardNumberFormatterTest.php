<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Test\Fixture\Formatter;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use TheWebSolver\Codegarage\PaymentCard\Traits\QueueBasedFormatter;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

class CardNumberFormatterTest extends TestCase {
	/** @param list<string|int> $breakpoints */
	#[Test]
	#[DataProviderExternal( Formatter::class, 'provideCardNumberWithBreakpoints' )]
	public function itFormatsCardNumberBasedOnBreakpoint( array $breakpoints, string|int $cardNumber, string $expected ): void {
		foreach ( $this->getFormatters() as $formatter ) {
			$formatter->setBreakpoint( ...$breakpoints );

			$this->assertSame( array_map( intval( ... ), $breakpoints ), $formatter->getBreakpoint() );
			$this->assertSame( $expected, $formatter->format( $cardNumber ) );
		}
	}

	/** @return Formatter[] */
	private function getFormatters(): array {
		return [
			new class() extends Formatter {
				use RegexBasedFormatter;
			},
			new class() extends Formatter {
				use QueueBasedFormatter;
			},
		];
	}
}
