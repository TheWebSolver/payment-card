<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use TheWebSolver\Codegarage\PaymentCard\Event\CardResolving;

interface ResolvingAction {
	/**
	 * Provides resolver being used to resolve the card type when factory is creating card.
	 */
	public function with( ResolvesCard $resolver ): self;

	/**
	 * Handles resolved card type.
	 */
	public function handle( CardResolving $event ): void;
}
