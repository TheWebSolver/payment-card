<?php
/**
 * Payment Card Formatter.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use SplQueue;

trait Formatter {
	/** @return int[] */
	abstract public function getGap(): array;

	public function format( string|int $cardNumber ): string {
		$cardNumber = (string) $cardNumber;
		$formatted  = '';
		$gapStack   = $this->getGapStack();
		$expectedAt = $gapStack->dequeue();
		$cardLength = strlen( $cardNumber );

		for ( $i = 0; $i < $cardLength; $i++ ) {
			if ( $i === $expectedAt ) {
				$formatted  .= ' ';
				$expectedAt = $gapStack->isEmpty() ? null : $gapStack->dequeue();
			}

			$formatted .= $cardNumber[ $i ];
		}

		return $formatted;
	}

	/** @return SplQueue<int> */
	private function getGapStack(): SplQueue {
		/** @var SplQueue<int> */
		$queue = new SplQueue();

		foreach ( $this->getGap() as $gap ) {
			$queue->enqueue( $gap );
		}

		return $queue;
	}
}
