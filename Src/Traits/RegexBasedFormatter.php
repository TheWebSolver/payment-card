<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait RegexBasedFormatter {
	use BreakpointGetter;

	/** @var array{0:string,1:string,2:int} */
	private array $breakPointArgs;

	/** @no-named-arguments */
	public function setBreakpoint( string|int $number, string|int ...$numbers ): static {
		Asserter::isProcessing( name: 'breakpoint' );

		$pattern              = $replacement = '';
		$numbers              = [ $number, ...$numbers ];
		$secondBreakpointStep = array_key_first( $numbers );

		foreach ( $numbers as $step => &$breakpoint ) {
			$breakpoint   = $this->breakpoint[] = Asserter::assertSingleSize( $breakpoint );
			$chunkSize    = $secondBreakpointStep === $step ? $breakpoint : $breakpoint - (int) $numbers[ $step - 1 ];
			$pattern     .= "(\d{{$chunkSize}})";
			$replacement .= $secondBreakpointStep === $step ? '$1' : ' $' . ( $step + 1 );
		}

		$this->breakPointArgs = [ $pattern, $replacement, $breakpoint ];

		return $this;
	}

	public function format( string|int $cardNumber ): string {
		[ $pattern, $replacement, $lastBreakpoint ] = $this->breakPointArgs;
		$cardNumber                                 = (string) $cardNumber;

		if ( ( $cardLength = strlen( $cardNumber ) ) > $lastBreakpoint ) {
			$remainingChunkSize = $cardLength - $lastBreakpoint;
			$pattern           .= "(\d{{$remainingChunkSize}})";
			$replacement       .= ' $' . ( count( $this->breakpoint ) + 1 );
		}

		return preg_replace( "/$pattern/", $replacement, $cardNumber ) ?? Asserter::formattingFailed( $cardNumber );
	}
}
