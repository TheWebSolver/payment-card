<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvedAction;

class ResolvingCardHandler implements ResolvedAction {
	private ResolvesCard $resolver;

	public function with( ResolvesCard $resolver ): ResolvedAction {
		$this->resolver = $resolver;

		return $this;
	}

	public function handle( CardCreated $event ): void {
		$this->resolver->validate( $event );
	}
}
