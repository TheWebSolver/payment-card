<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;

interface CreatingAction {
	/**
	 * @param CardCreated<TCardType> $event
	 * @template TCardType of CardType
	 */
	public function handle( CardCreated $event ): void;
}
