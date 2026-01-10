<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Fixture;

use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Traits\CardResolver;

class Validator {
	use CardResolver {
		CardResolver::resolve as public;
	}

	/** @var CardFactory[] */
	private array $factories;

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
