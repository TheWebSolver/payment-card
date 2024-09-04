<?php
/**
 * Payment Card Formatter.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use SplQueue;
use TheWebSolver\Codegarage\PaymentCard\Card;

trait Formatter {
	/** @var int[] */
	public array $gap;

	/** @return int[] */
	public function getGap(): array {
		return $this->gap;
	}

	public function setGap( string|int $gap, string|int ...$gaps ): static {
		Card::isProcessing( name: 'gap' );

		$this->gap = array_map(
			callback: static fn( mixed $size ): int => Card::assertSingleSize( $size ),
			array: array( $gap, ...$gaps )
		);

		return $this;
	}

	public function format( string|int $cardNumber ): string {
		$cardNumber = (string) $cardNumber;
		$formatted  = '';
		$gapStack   = $this->getGapStack();
		$expectedAt = $gapStack->dequeue();
		$cardLength = strlen( $cardNumber );

		for ( $i = 0; $i < $cardLength; $i++ ) {
			if ( $i === $expectedAt ) {
				$formatted .= ' ';
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
