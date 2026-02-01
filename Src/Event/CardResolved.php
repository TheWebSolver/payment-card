<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Event;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;

readonly class CardResolved {
	public const CURRENT_CARD_ERROR = 'Impossible to get current card created when factory #%s is not creating cards';
	public const CHECK_NEXT_INFO    = 'Checking against next card...';

	/** @placeholder `%d`: Current factory number */
	public const RESOURCE_ERROR = 'Could not resolve payload resource path from factory #%d';
	/** @placeholder: `%s`: Payload resource realpath  */
	public const RESOURCE_INFO = 'Payload resource path: %s';
	/** @placeholder: `1:` Factory started or finished, `2:` Current factory number */
	public const FACTORY_STATUS_INFO = '%1$s resolving card number "%2$s" against payload from Factory #%3$d';
	/** @placeholder `1:` Factory resolved or not, `2:` Current factory number */
	public const FACTORY_RESOLVED_INFO = '%1$s Card against payload from Factory #%2$d';
	/** @placeholder `1:` Card resolved or not, `2:` Card name */
	public const CARD_RESOLVED_INFO = '%1$s card number as "%2$s" card';

	/** @param CardFactory<CardType> $factory */
	public function __construct(
		public CardFactory $factory,
		public int $factoryNumber,
		public string|int $cardNumber,
		private ?Status $status = null,
		private ?CardCreated $current = null
	) {}

	public function started(): bool {
		return null !== $this->status;
	}

	/** @phpstan-assert-if-true =CardCreated $this->current */
	public function isCreating(): bool {
		return Status::Omitted === $this->status && null !== $this->current;
	}

	public function finished(): bool {
		return $this->isCreating() && array_key_last( $this->factory->getPayload() ) === $this->current->payloadIndex;
	}

	public function isSuccess(): bool {
		return Status::Success === $this->status;
	}

	/**
	 * @throws LogicException When this method is invoked when factory is not creating cards.
	 * @see self::isCreating() Returns true when card created event is registered. Always check.
	 */
	public function current(): CardCreated {
		return $this->current ?? throw new LogicException( sprintf( self::CURRENT_CARD_ERROR, $this->factoryNumber ) );
	}

	/** @throws LogicException When cannot retrieve resource path from factory. */
	public function resourceInfo(): string {
		return sprintf(
			self::RESOURCE_INFO,
			realpath( $this->factory->getResourcePath() ?: $this->throwResourceError() ) ?: $this->throwResourceError()
		);
	}

	public function factoryStatusInfo(): string {
		$status = ! $this->started() ? 'Started' : 'Finished';

		return sprintf( self::FACTORY_STATUS_INFO, $status, $this->cardNumber, $this->factoryNumber );
	}

	/**
	 * @throws LogicException When this method is invoked when factory is not creating card.
	 * @throws LogicException When payload data does not follow Card Schema.
	 */
	public function cardResolvedInfo( Status $status ): string {
		try {
			return sprintf( self::CARD_RESOLVED_INFO, $status->resolvedState(), $this->current()->cardName() );
		} catch ( LogicException $e ) {
			throw new LogicException( trim( $e->getMessage(), '.' ) . " from factory #{$this->factoryNumber}." );
		}
	}

	public function factoryResolvedInfo(): string {
		$args = ( $this->isSuccess() ? Status::Success : Status::Failure )->resolvedState();

		return sprintf( self::FACTORY_RESOLVED_INFO, $args, $this->factoryNumber );
	}

	private function throwResourceError(): never {
		throw new LogicException( sprintf( self::RESOURCE_ERROR, $this->factoryNumber ) );
	}
}
