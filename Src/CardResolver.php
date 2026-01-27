<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolved;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvedAction;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver as ResolverTrait;

class CardResolver implements ResolvesCard {
	use ResolverTrait {
		ResolverTrait::handleResolvedCard as handleResolvedCardFrom;
		ResolverTrait::resolve as resolveUsing;
	}

	/** @var non-empty-list<CardFactory<CardType>> */
	private array $factories;
	/** @var array{CardFactory<CardType>,int} Current factory and its iteration count (index + 1). */
	private array $currentFactory;
	private string|int $cardNumber;
	private ?ResolvedAction $handler = null;

	public function for( string|int $cardNumber ): ResolvesCard {
		$this->cardNumber ??= $cardNumber;

		return $this;
	}

	public function using( CardFactory $factory, CardFactory ...$factories ): ResolvesCard {
		$this->factories ??= [ $factory, ...$factories ];

		return $this;
	}

	public function handleWith( ResolvedAction $handler ): ResolvesCard {
		$this->handler ??= $handler->resolvedWith( $this );

		return $this;
	}

	public function resolve( bool $exitOnResolve ): CardType|array|null {
		$resolved = [];

		foreach ( $this->factories as $index => $factory ) {
			$this->currentFactory = [ $factory, $factoryNumber = $index + 1 ];

			$this->handler?->handle( new CardResolved( $factory, $factoryNumber, $this->cardNumber ) );

			$resolvedCards = $this->resolveUsing( $this->cardNumber, $factory, $exitOnResolve );
			$status        = ( $hasNoCards = null === $resolvedCards ) ? Status::Failure : Status::Success;

			$this->handler?->handle( new CardResolved( $factory, $factoryNumber, $this->cardNumber, $status ) );

			if ( $hasNoCards ) {
				continue;
			}

			if ( $exitOnResolve ) {
				return $resolvedCards instanceof CardType ? $resolvedCards : end( $resolvedCards );
			}

			$resolved[ $index ] = $resolvedCards;
		}

		return $resolved ? $resolved : null;
	}

	/** @param CardCreated<CardType> $current */
	private function handleResolvedCard( CardCreated $current ): bool {
		[$factory, $factoryNumber] = $this->currentFactory;
		$status                    = $this->handleResolvedCardFrom( $current );

		$this->handler?->handle( new CardResolved( $factory, $factoryNumber, $this->cardNumber, Status::Omitted, $current ) );

		return $status;
	}
}
