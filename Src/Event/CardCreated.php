<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

final class CardCreated {
	public const NOT = 'Card not instantiated. Verify using "' . __CLASS__ . '::isSkipped()" method if card creation is skipped?';
	/** @placeholder `%s:` Payload index */
	public const NO_OR_INVALID_NAME = 'Payload data does not follow card schema. No "name" key or value is not of string type for payload index "%s".';

	private bool $stopPropagation = false;

	/**
	 * @param TCardType|null $card The card instance created. null if card creation is skipped.
	 * @template TCardType of CardType
	 */
	public function __construct(
		public readonly ?CardType $card,
		public readonly string|int $payloadIndex,
		public readonly mixed $payloadValue
	) {}

	/** @phpstan-assert-if-true null $this->card */
	public function isSkipped(): bool {
		return null === $this->card;
	}

	/** @throws LogicException When payload data does not follow Card Schema. */
	public function cardName(): string {
		return $this->card?->getName() ?? $this->getNameFromPayload();
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
