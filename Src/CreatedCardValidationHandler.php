<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardValidationAction;

class CreatedCardValidationHandler implements CardValidationAction {
	private ResolvesCard $resolver;

	public function with( ResolvesCard $resolver ): CardValidationAction {
		$this->resolver = $resolver;

		return $this;
	}

	public function handle( CardCreated $event ): void {
		$this->resolver->validate( $event );
	}
}
