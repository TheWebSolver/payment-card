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
	/** @placeholder: `1:` Payload index, `2:` Card name */
	final public const PAYLOAD_INDEX_ALREADY_COVERED = 'Duplicate payload index found "%1$s" for card "%2$s" already created by factory.';

	/** @var non-empty-list<CardFactory<CardType>> */
	private array $factories;
	/** @var Status[] */
	private array $coveredCards;
	/** @var int<0,max> */
	private int $currentFactoryIndex;
	private string|int $cardNumber;
	private bool $exitOnResolve;
	private ?ResolvedAction $resolvedHandler = null;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<CardType> */
	private array $validCards;

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

	public function with( ResolvedAction $handler ): ResolvesCard {
		$this->resolvedHandler ??= $handler->with( $this );

		return $this;
	}

	public function resolve( ResolvingAction $handler = new ResolvingCardHandler() ): CardType|array|null {
		$handler->with( $this );

		$resolved = [];

		foreach ( $this->factories as $index => $factory ) {
			$this->currentFactoryIndex = $index;

			if ( $validCards = $this->getValidCardsCreatedByCurrentFactory( $handler ) ) {
				if ( $this->exitOnResolve ) {
					return end( $validCards );
				}

				$resolved[ $index ] = $validCards;
			}
		}

		return $resolved ? $resolved : null;
	}

	public function validate( CardCreated $current ): void {
		$this->validateAndRegisterValidCardFrom( $current );

		[$factory, $number] = $this->getCurrentFactory();

		$this->resolvedHandler?->handle(
			new CardResolved( $factory, $number, $this->cardNumber, Status::Omitted, $current )
		);
	}

	/** @return ?non-empty-list<CardType> */
	protected function getValidCardsCreatedByCurrentFactory( ResolvingAction $handler ): ?array {
		$factory = $this->factories[ $index = $this->currentFactoryIndex ];

		$this->resolvedHandler?->handle( new CardResolved( $factory, $index + 1, $this->cardNumber ) );

		iterator_to_array( $factory->lazyLoad( $handler ) );

		$validCards = $this->validCards ?? null;
		$status     = null === $validCards ? Status::Failure : Status::Success;

		$this->resolvedHandler?->handle( new CardResolved( $factory, $index + 1, $this->cardNumber, $status ) );

		unset( $this->validCards );

		return $validCards;
	}

	/** @return array{CardFactory<CardType>,positive-int} */
	private function getCurrentFactory(): array {
		return [ $this->factories[ $this->currentFactoryIndex ], $this->currentFactoryIndex + 1 ];
	}

	/**
	 * @param CardCreated<CardType> $current
	 * @throws LogicException When card with same payload index is already validated.
	 */
	private function ensureCurrentCardIsNotValidatedBefore( CardCreated $current ): void {
		isset( $this->coveredCards[ $index = $current->payloadIndex ] )
			&& throw new LogicException( sprintf( self::PAYLOAD_INDEX_ALREADY_COVERED, $index, $current->cardName() ) );
	}

	/** @param CardCreated<CardType> $current */
	private function validateAndRegisterStatusFrom( CardCreated $current ): Status {
		return $this->coveredCards[ $current->payloadIndex ] = ! $current->isCreatableCard
			? Status::Omitted
			: ( $current->card()->isNumberValid( $this->cardNumber ) ? Status::Success : Status::Failure );
	}

	/** @param CardCreated<CardType> $current */
	private function validateAndRegisterValidCardFrom( CardCreated $current ): void {
		$this->ensureCurrentCardIsNotValidatedBefore( $current );

		Status::Success === ( $status = $this->validateAndRegisterStatusFrom( $current ) )
			&& $current->isCreatableCard
			&& ( $this->validCards[] = $current->card() );

		$current->stopPropagation( Status::Success === $status && $this->exitOnResolve );
	}
}
