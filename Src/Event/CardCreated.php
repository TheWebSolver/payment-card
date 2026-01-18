<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

/** @template TCardType of object */
final readonly class CardCreated {
	/** @param TCardType|null $card The card instance created. null if is not a creatable card or other custom logic. */
	public function __construct(
		public ?object $card,
		public string|int $payloadIndex,
		public mixed $payloadValue,
		public bool $isCreatableCard
	) {}
}
