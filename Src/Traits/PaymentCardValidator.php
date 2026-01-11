<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

/** This is intended to only be used inside concrete that implements `CardInterface` */
trait PaymentCardValidator {
	use Matcher;

	abstract public function needsLuhnCheck(): bool;
	abstract public function getIdRange(): array;
	abstract public function getLength(): array;
	abstract public function getCode(): array;

	public function isNumberValid( mixed $number ): bool {
		return static::matchesAllowedPattern( $number )
			&& is_string( $number )
			&& static::matchesLength( $this->getLength(), $number )
			&& static::matchesLuhnAlgorithm( $number, shouldRun: $this->needsLuhnCheck() )
			&& static::matchesIdRange( $this->getIdRange(), $number );
	}

	public function isCodeValid( mixed $code ): bool {
		return ( is_string( $code ) || is_int( $code ) ) && strlen( (string) $code ) === ( $this->getCode()['size'] );
	}
}
