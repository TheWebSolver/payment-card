<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolved;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

interface ResolvesCard {
	/**
	 * Sets card number that resolves the card type.
	 *
	 * This must be implemented as an immutable method in a way that resolver state does not change on subsequent invocation.
	 *
	 * @param bool $exitOnResolve Whether resolving should stop once card number is valid and card type is resolved.
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
	 * Sets handler that handles card type during factory creating cards and before/after resolver resolves.
	 *
	 * This must be implemented as an immutable method in a way that resolver state does not change on subsequent invocation.
	 */
	public function with( ResolvingAction $resolvingHandler, ?ResolvedAction $resolvedHandler = null ): self;

	/**
	 * Resolves card type instance after validating with the provided card number.
	 *
	 * @return CardType|non-empty-array<int,non-empty-list<CardType>>|null
	 * @throws LogicException When resolving action handler is not provided.
	 */
	public function resolve(): CardType|array|null;

	/**
	 * Validates card type created by the current factory and returns its status.
	 *
	 * This may be implemented as a mutable method to register covered cards, resolved status, etc. to change the resolver state.
	 *
	 * @param CardCreated<CardType> $event
	 */
	public function validate( CardCreated $event ): Status;

	/**
	 * Handles created card type's resolved state for the current factory.
	 */
	public function handleResolved( CardResolved $event ): void;

	/**
	 * Gets all card status that are covered when resolving card type.
	 *
	 * @return Status[]
	 */
	public function getCoveredCardStatus(): array;

	/**
	 * Gets card number that is validated against card types created by the factory.
	 */
	public function getCardNumber(): string|int;

	/**
	 * Gets current factory instance and its iteration count when resolving card type.
	 *
	 * @return array{0:CardFactory<CardType>,1:int} Factory instance as first item and its iteration count as second.
	 */
	public function getCurrentFactory(): array;

	/**
	 * Determines whether subsequent card creation should be stopped once a card type has been resolved for given card number.
	 */
	public function shouldExitOnResolve(): bool;
}
