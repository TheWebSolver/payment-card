<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use TheWebSolver\Codegarage\PaymentCard\Event\CardResolving;

interface ResolvedAction {
	/**
	 * Sets card resolver that resolves card number.
	 */
	public function with( ResolvesCard $resolver ): self;

	/**
	 * Handles resolved card type.
	 */
	public function handle( CardResolving $event ): void;
}
