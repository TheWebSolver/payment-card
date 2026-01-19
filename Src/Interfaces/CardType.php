<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

interface CardType {
	/**
	 * Gets the card type such as Debit Card, Credit Card, Gift Card, etc.
	 */
	public function getType(): string;

	/**
	 * Gets the card's human readable nice-name.
	 */
	public function getName(): string;

	/**
	 * Sets the card's human readable nice-name.
	 */
	public function setName( string $name ): static;

	/**
	 * Validates the given card number.
	 */
	public function isNumberValid( string|int $cardNumber ): bool;
}
