<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;
use TheWebSolver\Codegarage\PaymentCard\Event\PaymentCardCreated;

trait PaymentCardResolver {
	/** @var Status[] */
	private array $coveredCards;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<PaymentCard> */
	private array $resolvedCards;
	/** @var array{string|int,bool} */
	private array $resolveArguments;

	/** @return Status[] */
	public function getCoveredCardStatus(): array {
		return $this->coveredCards ?? [];
	}

	/** @return ($exitOnResolve is true ? PaymentCard|null : non-empty-list<PaymentCard>|null) */
	private function resolve( string|int $cardNumber, PaymentCardFactory $factory, bool $exitOnResolve = true ): null|PaymentCard|array {
		$this->resolveArguments = [ $cardNumber, $exitOnResolve ];
		$generator              = $factory->lazyLoad( $this->handleResolvedCard( ... ) );

		while ( $generator->valid() ) {
			$generator->next();
		}

		$resolvedCards = $this->resolvedCards ?? [];

		unset( $this->resolveArguments, $this->resolvedCards );

		return $exitOnResolve ? ( reset( $resolvedCards ) ?: null ) : ( $resolvedCards ?: null );
	}

	private function handleResolvedCard( PaymentCardCreated $event ): bool {
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
