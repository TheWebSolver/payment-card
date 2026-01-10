<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Data;

use TheWebSolver\Codegarage\PaymentCard\CardInterface;

final readonly class PaymentCardTypeCreated {
	public function __construct(
		public ?CardInterface $card,
		public string|int $payloadIndex,
		public mixed $payloadValue,
		public bool $isCreatableCard
	) {}
}
