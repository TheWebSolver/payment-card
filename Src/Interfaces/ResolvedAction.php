<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use TheWebSolver\Codegarage\PaymentCard\Event\CardResolved;

interface ResolvedAction {
	/**
	 * Sets card resolver that resolves card number.
	 */
	public function resolvedWith( ResolvesCard $resolver ): self;

	/**
	 * Handles resolved cord type.
	 */
	public function handle( CardResolved $event ): void;
}
