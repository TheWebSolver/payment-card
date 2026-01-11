<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Fixture;

use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;
use TheWebSolver\Codegarage\PaymentCard\Traits\PaymentCardResolver;

class PaymentCardValidator {
	use PaymentCardResolver {
		PaymentCardResolver::resolve as public;
	}

	/** @var PaymentCardFactory[] */
	private array $factories;

	public function __construct( PaymentCardFactory $factory, PaymentCardFactory ...$factories ) {
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
