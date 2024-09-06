<?php
/**
 * Payment Card Formatter with breakpoint based on splQueue.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use SplQueue;
use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait QueueBasedFormatter {
	use BreakpointGetter;

	public function setBreakpoint( string|int $number, string|int ...$numbers ): static {
		Asserter::isProcessing( name: 'breakpoint' );

		$this->breakpoint = array_map( Asserter::assertSingleSize( ... ), array: func_get_args() );

		return $this;
	}

	public function format( string|int $cardNumber ): string {
		$cardNumber = (string) $cardNumber;
		$formatted  = '';
		$stack      = $this->getBreakpointStack();
		$expectedAt = $stack->dequeue();
		$cardLength = strlen( $cardNumber );

		for ( $i = 0; $i < $cardLength; $i++ ) {
			if ( $i === $expectedAt ) {
				$formatted .= ' ';
				$expectedAt = $stack->isEmpty() ? null : $stack->dequeue();
			}

			$formatted .= $cardNumber[ $i ];
		}

		return $formatted;
	}

	/** @return SplQueue<int> */
	private function getBreakpointStack(): SplQueue {
		/** @var SplQueue<int> */
		$queue = new SplQueue();

		foreach ( $this->getBreakpoint() as $number ) {
			$queue->enqueue( $number );
		}

		return $queue;
	}
}
