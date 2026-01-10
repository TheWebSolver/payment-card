<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\CardInterface;
use TheWebSolver\Codegarage\PaymentCard\Data\PaymentCardTypeCreated;

trait CardResolver {
	/** @var Status[] */
	private array $coveredCards;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<CardInterface> */
	private array $resolvedCards;
	/** @var array{string|int,bool} */
	private array $resolveArguments;

	/** @return Status[] */
	public function getCoveredCardIndices(): array {
		return $this->coveredCards ?? [];
	}

	/** @return ($exitOnResolve is true ? CardInterface|null : non-empty-list<CardInterface>|null) */
	private function resolve( string|int $cardNumber, CardFactory $factory, bool $exitOnResolve = true ): null|CardInterface|array {
		$this->resolveArguments = [ $cardNumber, $exitOnResolve ];
		$generator              = $factory->lazyLoad( $this->handleResolvedCard( ... ) );

		while ( $generator->valid() ) {
			$generator->next();
		}

		$resolvedCards = $this->resolvedCards ?? [];

		unset( $this->resolveArguments, $this->resolvedCards );

		return $exitOnResolve ? ( reset( $resolvedCards ) ?: null ) : ( $resolvedCards ?: null );
	}

	private function handleResolvedCard( PaymentCardTypeCreated $event ): bool {
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
