<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Fixture;

use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\Traits\PaymentCardResolver;

class PaymentCardValidator {
	use PaymentCardResolver {
		PaymentCardResolver::resolve as public;
	}

	/** @var non-empty-list<CardFactory<PaymentCard>> */
	private array $factories;

	/**
	 * @param CardFactory<PaymentCard> $factory
	 * @param CardFactory<PaymentCard> ...$factories
	 * @no-named-arguments
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
