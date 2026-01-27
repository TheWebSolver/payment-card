<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

/** @template TCardType of CardType */
final readonly class CardCreated {
	public const NOT = 'Card not instantiated using given payload value. Verify using "' . __CLASS__ . '::$isCreatableCard" if it a creatable card?';

	/** @param TCardType|null $card The card instance created. null if is not a creatable card. */
	public function __construct(
		private ?CardType $card,
		public string|int $payloadIndex,
		public mixed $payloadValue,
		public bool $isCreatableCard
	) {}

	/**
	 * Gets the created card type instance.
	 *
	 * @throws LogicException When card type is not creatable.
	 * @see self::$isCreatableCard To check if card type is creatable or not.
	 */
	public function card(): CardType {
		return $this->card ?? throw new LogicException( self::NOT );
	}
}
