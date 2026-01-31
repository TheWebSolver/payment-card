<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

/** @template TCardType of CardType */
final class CardCreated {
	public const NOT = 'Card not instantiated. Verify using "' . __CLASS__ . '::$isCreatableCard" if it a creatable card?';
	/** @placeholder `%s:` Payload index */
	public const NO_OR_INVALID_NAME = 'Payload data does not follow card schema. No "name" key or value is not of string type for payload index "%s".';

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

	/** @throws LogicException When payload data does not follow Card Schema. */
	public function cardName(): string {
		try {
			return $this->card()->getName();
		} catch ( LogicException ) {
			return $this->getNameFromPayload();
		}
	}

	public function stopPropagation( bool $stop = true ): void {
		$this->stopPropagation = $stop;
	}

	public function shouldStopPropagation(): bool {
		return $this->stopPropagation;
	}

	private function getNameFromPayload(): string {
		return is_array( $data = $this->payloadValue ) && is_string( $name = $data['name'] ?? null )
			? $name
			: throw new LogicException( sprintf( self::NO_OR_INVALID_NAME, $this->payloadIndex ) );
	}
}
