<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;

trait CardResolver {
	/** @var Status[] */
	private array $coveredCards;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<CardType> */
	private array $resolvedCards;
	/** @var array{string|int,bool} Card Number & whether should exit on resolve. */
	private array $resolveArguments;

	/** @return Status[] */
	public function getCoveredCardStatus(): array {
		return $this->coveredCards ?? [];
	}

	/**
	 * @param CardFactory<TCardType> $factory
	 * @return ($exitOnResolve is true ? TCardType|null : non-empty-list<TCardType>|null)
	 * @template TCardType of CardType
	 */
	private function resolve( string|int $cardNumber, CardFactory $factory, bool $exitOnResolve = true ): null|CardType|array {
		$this->resolveArguments = [ $cardNumber, $exitOnResolve ];

		iterator_to_array( $factory->lazyLoad( $this->handleResolvedCard( ... ) ) );

		$resolvedCards = $this->resolvedCards ?? [];

		unset( $this->resolveArguments, $this->resolvedCards );

		return $exitOnResolve ? ( end( $resolvedCards ) ?: null ) : ( $resolvedCards ?: null );
	}

	/** @param CardCreated<CardType> $event */
	private function handleResolvedCard( CardCreated $event ): bool {
		[$cardNumber, $exitOnResolve] = $this->resolveArguments;
		$status                       = $event->isCreatableCard
			? ( $event->card?->isNumberValid( $cardNumber ) ? Status::Success : Status::Failure )
			: Status::Omitted;

		$this->coveredCards[ $event->payloadIndex ] = $status;

		if ( $event->card && Status::Success === $status ) {
			$this->resolvedCards[] = $event->card;

			return ! $exitOnResolve;
		}

		return true;
	}
}
