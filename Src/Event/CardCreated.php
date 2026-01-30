<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

/** @template TCardType of CardType */
final class CardCreated {
	public const NOT = 'Card not instantiated using given payload value. Verify using "' . __CLASS__ . '::$isCreatableCard" if it a creatable card?';

	private bool $stopPropagation = false;

	/** @param TCardType|null $card The card instance created. null if is not a creatable card. */
	public function __construct(
		private readonly ?CardType $card,
		public readonly string|int $payloadIndex,
		public readonly mixed $payloadValue,
		public readonly bool $isCreatableCard
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

	public function stopPropagation( bool $stop = true ): void {
		$this->stopPropagation = $stop;
	}

	public function shouldStopPropagation(): bool {
		return $this->stopPropagation;
	}
}
