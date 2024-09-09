<?php
/**
 * Napas Card
 *
 * @package TheWebSolver/Codegarage/Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Resource;

use TheWebSolver\Codegarage\PaymentCard\CardType;

class NapasCard extends CardType {
	public function __construct( private readonly string $cardType ) {
		parent::__construct();
	}

	protected function getType(): string {
		return $this->cardType;
	}
}
