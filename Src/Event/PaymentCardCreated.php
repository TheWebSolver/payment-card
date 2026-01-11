<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use TheWebSolver\Codegarage\PaymentCard\PaymentCard;

final readonly class PaymentCardCreated {
	public function __construct(
		public ?PaymentCard $card,
		public string|int $payloadIndex,
		public mixed $payloadValue,
		public bool $isCreatableCard
	) {}
}
