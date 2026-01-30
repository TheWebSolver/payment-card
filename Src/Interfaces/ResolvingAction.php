<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

interface ResolvingAction extends CreatingAction {
	/**
	 * Provides resolver being used to resolve the card type when factory is creating card.
	 */
	public function with( ResolvesCard $resolver ): self;
}
