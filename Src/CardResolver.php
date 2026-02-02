<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolving;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvedAction;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvingAction;

class CardResolver implements ResolvesCard {
	/** @placeholder: `1:` Payload index, `2:` Current card name, `3:` Previously covered card name */
	final public const PAYLOAD_ALREADY_COVERED = 'Duplicate payload index found "%1$s" for card "%2$s" already covered as "%3$s" card.';

	/** @var non-empty-list<CardFactory<CardType>> */
	private array $factories;
	/** @var array<array{status:Status,name:string}> */
	private array $coveredCards;
	/** @var int<0,max> */
	private int $currentFactoryIndex;
	private string|int $cardNumber;
	private bool $exitOnResolve;
	private ?ResolvingAction $resolvingHandler = null;

	/*
	| ----------------------------------------------------------------------------
	| Artifacts when resolving a Card. Must be cleared once resolve is complete.
	| ----------------------------------------------------------------------------
	*/

	/** @var non-empty-list<CardType> */
	private array $validCards;

	public function getCoveredCards(): array {
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

	public function with( ResolvingAction $handler ): ResolvesCard {
		$this->resolvingHandler ??= $handler->with( $this );

		return $this;
	}

	public function resolve( ResolvedAction $handler = new ResolvingCardHandler() ): CardType|array|null {
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

		$this->resolvingHandler?->handle( new CardResolving( $factory, $number, $this->cardNumber, $current ) );
	}

	/** @return ?non-empty-list<CardType> */
	protected function getValidCardsCreatedByCurrentFactory( ResolvedAction $handler ): ?array {
		$factory = $this->factories[ $index = $this->currentFactoryIndex ];

		$this->resolvingHandler?->handle( new CardResolving( $factory, $index + 1, $this->cardNumber, null ) );

		iterator_to_array( $factory->lazyLoad( $handler ) );

		$validCards = $this->validCards ?? null;
		$status     = null === $validCards ? Status::Failure : Status::Success;

		$this->resolvingHandler?->handle( new CardResolving( $factory, $index + 1, $this->cardNumber, $status ) );

		unset( $this->validCards );

		return $validCards;
	}

	/** @return array{CardFactory<CardType>,positive-int} */
	private function getCurrentFactory(): array {
		return [ $this->factories[ $this->currentFactoryIndex ], $this->currentFactoryIndex + 1 ];
	}

	private function throwIfPayloadIndexAlreadyCovered( CardCreated $current ): void {
		( $coveredCard = ( $this->coveredCards[ $index = $current->payloadIndex ] ?? false ) )
			&& throw new LogicException( sprintf( self::PAYLOAD_ALREADY_COVERED, $index, $current->cardName(), $coveredCard['name'] ) );
	}

	private function setCoveredCardFrom( CardCreated $current ): Status {
		$this->throwIfPayloadIndexAlreadyCovered( $current );

		/** @disregard P1006 Expected type 'object'. Found 'TCardType|null' */
		$this->coveredCards[ $current->payloadIndex ] = [
			'name'   => $current->cardName(),
			'status' => $status = $current->isSkipped()
				? Status::Omitted
				: ( $current->card->isNumberValid( $this->cardNumber ) ? Status::Success : Status::Failure ),
		];

		return $status;
	}

	private function validateAndRegisterValidCardFrom( CardCreated $current ): void {
		Status::Success === ( $status = $this->setCoveredCardFrom( $current ) )
			&& ! $current->isSkipped()
			&& ( $this->validCards[] = $current->card );

		$current->stopPropagation( Status::Success === $status && $this->exitOnResolve );
	}
}
