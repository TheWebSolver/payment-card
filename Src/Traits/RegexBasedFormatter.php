<?php
/**
 * Payment Card Formatter with breakpoint based on Regex pattern.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait RegexBasedFormatter {
	use BreakpointGetter;

	/** @var array{0:string,1:string,2:int} */
	private array $breakPointArgs;

	public function setBreakpoint( string|int $number, string|int ...$numbers ): static {
		Asserter::isProcessing( name: 'breakpoint' );

		$pattern = $replacement = '';
		$numbers = array( $number, ...$numbers );
		$first   = array_key_first( $numbers );

		foreach ( $numbers as $step => &$checksum ) {
			$checksum     = $this->breakpoint[] = Asserter::assertSingleSize( $checksum );
			$count        = $first === $step ? $checksum : $checksum - (int) $numbers[ (int) $step - 1 ];
			$replacement .= $first === $step ? '$1' : ' $' . ( (int) $step + 1 );
			$pattern     .= '(\d{' . $count . '})';
		}

		$this->breakPointArgs = array( $pattern, $replacement, $checksum );

		return $this;
	}

	public function format( string|int $cardNumber ): string {
		[ $pattern, $replacement, $checksum ] = $this->breakPointArgs;

		if ( $checksum < ( $length = strlen( (string) $cardNumber ) ) ) {
			$remaining    = $length - $checksum;
			$pattern     .= '(\d{' . $remaining . '})';
			$replacement .= ' $' . ( count( $this->breakpoint ) + 1 );
		}

		return preg_replace( '/' . $pattern . '/', $replacement, (string) $cardNumber )
			?? Asserter::formattingFailed( (string) $cardNumber );
	}
}
