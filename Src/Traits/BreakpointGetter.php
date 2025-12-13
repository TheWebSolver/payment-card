<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

trait BreakpointGetter {
	/** @var list<int> */
	private array $breakpoint;

	/** @return list<int> */
	public function getBreakpoint(): array {
		return $this->breakpoint;
	}
}
