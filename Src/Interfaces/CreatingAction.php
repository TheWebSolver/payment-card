<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;

interface CreatingAction {
	/**
	 * Handles created card.
	 */
	public function handle( CardCreated $event ): void;
}
