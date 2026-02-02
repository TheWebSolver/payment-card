<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

interface ResolvesCard {
	/**
	 * Sets card number that resolves the card type.
	 *
	 * This must be implemented as an immutable method in a way that resolver state does not change on subsequent invocation.
	 *
	 * @param bool $exitOnResolve Whether or not factory should create subsequent cards for validation
	 *                            once the card type with the provided card number is resolved?
	 */
	public function when( string|int $cardNumber, bool $exitOnResolve = true ): self;

	/**
	 * Sets card factories to resolve the card type.
	 *
	 * This must be implemented as an immutable method in a way that resolver state does not change on subsequent invocation.
	 *
	 * @param CardFactory<TCardType> $factory
	 * @param CardFactory<TCardType> ...$factories
	 * @no-named-arguments
	 * @template TCardType of CardType
	 */
	public function using( CardFactory $factory, CardFactory ...$factories ): self;

	/**
	 * Sets handler that handles action before, during, and after factory has created cards.
	 *
	 * This must be implemented as an immutable method in a way that resolver state does not change on subsequent invocation.
	 */
	public function with( ResolvingAction $handler ): self;

	/**
	 * Resolves card type instance after validating with the provided card number.
	 *
	 * @return CardType|non-empty-array<int,non-empty-list<CardType>>|null
	 * @throws LogicException When validation fails.
	 * @see self::validate() Which handles validation process.
	 */
	public function resolve( ResolvedAction $handler ): CardType|array|null;

	/**
	 * Validates card type created by the current factory.
	 *
	 * This may be implemented as a mutable method to change the resolver state such as registering:
	 * - covered cards,
	 * - validated card(s), etc.
	 *
	 * @throws LogicException When card type with the same payload index is already validated.
	 * @throws LogicException When payload data does not follow card schema.
	 */
	public function validate( CardCreated $current ): void;

	/**
	 * Gets all covered cards' name and status when resolving card type.
	 *
	 * @return array<array{status:Status,name:string}>
	 */
	public function getCoveredCards(): array;
}
