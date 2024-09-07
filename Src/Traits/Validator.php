<?php
/**
 * Payment Card Validation methods.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\PaymentCard;

/**
 * This is intended to only be used inside concrete that implements
 * `TheWebSolver\Codegarage\PaymentCard\CardInterface`
 */
trait Validator {
	abstract public function getLength(): array;
	abstract public function getIdRange(): array;
	abstract public function getCode(): array;

	public function isNumberValid( mixed $number, bool $withLuhnAlgorithm = true ): bool {
		return PaymentCard::matchesAllowedPattern( $number )
			&& PaymentCard::matchesLength( $this->getLength(), $number )
			&& PaymentCard::matchesLuhnAlgorithm( $number, shouldRun: $withLuhnAlgorithm )
			&& PaymentCard::matchesIdRange( $this->getIdRange(), $number );
	}

	public function isCodeValid( mixed $code ): bool {
		return ( is_string( $code ) || is_int( $code ) )
			&& strlen( (string) $code ) === ( $this->getCode()[1] ?? false );
	}
}
