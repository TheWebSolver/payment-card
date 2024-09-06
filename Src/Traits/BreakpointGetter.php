<?php
/**
 * Breakpoint getter.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

trait BreakpointGetter {
	/** @var int[] */
	private array $breakpoint;

	/** @return int[] */
	public function getBreakpoint(): array {
		return $this->breakpoint;
	}
}
