<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Interfaces;

use Closure;
use Generator;
use RuntimeException;
use OutOfBoundsException;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

/** @template TCardType of CardType */
interface CardFactory {
	public const RESOURCE_PATH = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Resource';

	/**
	 * Gets payload to create card instance.
	 *
	 * @return non-empty-array<mixed>
	 */
	public function getPayload(): array;

	/**
	 * Gets payload resource path, if provided payload is a file path containing payload data.
	 *
	 * @return non-empty-string|null
	 */
	public function getResourcePath(): ?string;

	/**
	 * Gets the payload indices which should only be used to create card instance when lazily loaded.
	 *
	 * This method may return an empty array to indicate all payload data must be used to yield the card instances lazily.
	 * However, this method should not be used to determine whether a card should be instantiated using the create method.
	 *
	 * @return list<int|non-empty-string>
	 */
	public function getCreatableIndices(): array;

	/**
	 * Creates a card instance from the provided payload index.
	 *
	 * @return TCardType
	 * @throws RuntimeException When payload cannot be resolved.
	 * @throws OutOfBoundsException When provided payload index is not defined in resolved payload.
	 */
	public function create( string|int $payloadIndex ): CardType;

	/**
	 * Creates card instances lazily one at a time.
	 *
	 * @param null|Closure(CardCreated<TCardType>):bool $eventHandler When eventHandler returns false, the yielding process must be stopped.
	 * @return Generator<array-key,TCardType|null> Generator may yield null when creatable indices array is provided, and
	 *                                             current payload index does not exist in that array.
	 * @throws RuntimeException When payload cannot be resolved.
	 */
	public function lazyload( ?Closure $eventHandler = null ): Generator;
}
