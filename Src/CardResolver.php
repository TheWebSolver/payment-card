<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolved;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvedAction;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvingAction;

class CardResolver implements ResolvesCard {
	final public const RESOLVING_ACTION_NOT_DEFINED = 'Impossible to validate created card without resolving action.';

	/** @var non-empty-list<CardFactory<CardType>> */
	private array $factories;
	/** @var Status[] */
	private array $coveredCards;
	private bool $exitOnResolve;
	private string|int $cardNumber;
	private int $currentFactoryIndex;
	private ResolvingAction $resolvingHandler;
	private ?ResolvedAction $resolvedHandler = null;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<CardType> */
	private array $resolvedCards;

	public function shouldExitOnResolve(): bool {
		return $this->exitOnResolve ?? true;
	}

	public function getCardNumber(): string|int {
		return $this->cardNumber;
	}

	public function getCoveredCardStatus(): array {
		return $this->coveredCards;
	}

	public function when( string|int $cardNumber, bool $exitOnResolve = true ): ResolvesCard {
		$this->cardNumber    ??= $cardNumber;
		$this->exitOnResolve ??= $exitOnResolve;

		return $this;
	}

	public function using( CardFactory $factory, CardFactory ...$factories ): ResolvesCard {
		$this->factories ??= [ $factory, ...$factories ];

		return $this;
	}

	public function with( ResolvingAction $resolvingHandler, ?ResolvedAction $resolvedHandler = null ): ResolvesCard {
		$this->resolvingHandler ??= $resolvingHandler->with( $this );
		$this->resolvedHandler  ??= $resolvedHandler?->with( $this );

		return $this;
	}

	public function getCurrentFactory(): array {
		return [ $this->factories[ $this->currentFactoryIndex ], $this->currentFactoryIndex + 1 ];
	}

	public function resolve(): CardType|array|null {
		$resolved = [];

		foreach ( $this->factories as $index => $factory ) {
			if ( $validCards = $this->validatedCardsCreatedByCurrentFactory( $this->currentFactoryIndex = $index ) ) {
				if ( $this->shouldExitOnResolve() ) {
					return end( $validCards );
				}

				$resolved[ $index ] = $validCards;
			}
		}

		return $resolved ? $resolved : null;
	}

	public function validate( CardCreated $event ): Status {
		$status = ! $event->isCreatableCard ? Status::Omitted : (
			$event->card()->isNumberValid( $this->getCardNumber() ) ? Status::Success : Status::Failure
		);

		$this->coveredCards[ $event->payloadIndex ] = $status;

		Status::Success === $status && $event->isCreatableCard && ( $this->resolvedCards[] = $event->card() );

		return $status;
	}

	public function handleResolved( CardResolved $event ): void {
		$this->resolvedHandler?->handle( $event );
	}

	/**
	 * @return ?non-empty-list<CardType>
	 * @throws LogicException When resolving action is not provided.
	 */
	protected function validatedCardsCreatedByCurrentFactory( int $index ): ?array {
		$factory = $this->factories[ $index ];

		$this->resolvedHandler?->handle( new CardResolved( $factory, $index + 1, $this->getCardNumber() ) );

			iterator_to_array(
				$factory->lazyLoad( $this->resolvingHandler ?? throw new LogicException( self::RESOLVING_ACTION_NOT_DEFINED ) )
			);

			$resolvedCards = $this->resolvedCards ?? null;

			unset( $this->resolvedCards );

			$status = null === $resolvedCards ? Status::Failure : Status::Success;

			$this->resolvedHandler?->handle( new CardResolved( $factory, $index + 1, $this->getCardNumber(), $status ) );

			return $resolvedCards;
	}
}
