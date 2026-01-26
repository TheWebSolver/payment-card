<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Fixture;

use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;

class PaymentCardValidator {
	use CardResolver {
		CardResolver::resolve as public;
	}

	/** @var non-empty-list<CardFactory<CardType>> */
	private array $factories;

	/**
	 * @param CardFactory<TCardType> $factory
	 * @param CardFactory<TCardType> ...$factories
	 * @no-named-arguments
	 * @template TCardType of CardType
	 */
	public function __construct( CardFactory $factory, CardFactory ...$factories ) {
		$this->factories = [ $factory, ...$factories ];
	}

	public function validate( string|int $cardNumber ): bool {
		foreach ( $this->factories as $factory ) {
			if ( $this->resolve( $cardNumber, $factory ) ) {
				return true;
			}
		}

		return false;
	}
}
