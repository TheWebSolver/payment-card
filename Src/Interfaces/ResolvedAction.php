<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

interface CardValidationAction extends CardCreatingAction {
	/**
	 * Sets card resolver that resolves card number.
	 */
	public function with( ResolvesCard $resolver ): self;
}
