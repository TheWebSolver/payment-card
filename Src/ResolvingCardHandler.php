<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvingAction;

class ResolvingCardHandler implements ResolvingAction {
	private ResolvesCard $resolver;

	public function with( ResolvesCard $resolver ): ResolvingAction {
		$this->resolver = $resolver;

		return $this;
	}

	/** @param CardCreated<CardType> $event */
	public function handle( CardCreated $event ): void {
		$this->resolver->validate( $event );
	}
}
