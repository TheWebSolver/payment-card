<?php
/**
 * Payment Card Validation methods.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;

/**
 * This is intended to only be used inside concrete that implements
 * `TheWebSolver\Codegarage\PaymentCard\CardInterface`
 */
trait Validator {

	abstract public function getLength(): array;
	abstract public function getPattern(): array;
	abstract public function getCode(): array;

	public function isNumberValid( mixed $subject, bool $withLuhnAlgorithm = true ): bool {
		if ( ! is_scalar( $subject ) || ! $subject = Asserter::normalize( (string) $subject ) ) {
			return false;
		}

		if ( ! $this->matchesLengths( $subject ) ) {
			return false;
		}

		if ( $withLuhnAlgorithm && ! $this->matchesLuhnAlgorithm( $subject ) ) {
			return false;
		}

		return $this->matchesPatterns( $subject );
	}

	public function isCodeValid( mixed $subject ): bool {
		return is_scalar( $subject ) && strlen( (string) $subject ) === $this->getCode()[1];
	}

	protected function matchesLengths( string $subject ): bool {
		$count = strlen( $subject );

		foreach ( $this->getLength() as $length ) {
			if ( is_array( $length ) ) {
				[ $min, $max ] = $length;
				$isValid       = $count >= (int) $min && $count <= (int) $max;
			} else {
				$isValid = (int) $length === strlen( $subject );
			}

			if ( $isValid ) {
				return true;
			}
		}

		return false;
	}

	protected function matchesLuhnAlgorithm( string $subject ): bool {
		return class_exists( '\\TheWebSolver\\Codegarage\\LuhnAlgorithm' )
			? \TheWebSolver\Codegarage\LuhnAlgorithm::validate( value: $subject )
			: true;
	}

	protected function matchesPatterns( string $subject ): bool {
		foreach ( $this->getPattern() as $pattern ) {
			if ( $this->matchesPattern( $subject, $pattern ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @param string|int|string[]|int[] $pattern
	 */ // phpcs:ignore Squiz.Commenting.FunctionComment.ParamNameNoMatch
	protected function matchesPattern( string $subject, string|int|array $pattern ): bool {
		return is_array( $pattern )
		? $this->matchesBetween( $subject, ...$pattern )
		: $this->matchesExact( $subject, $pattern );
	}

	protected function matchesBetween( string $subject, string|int $min, string|int $max ): bool {
		$count = self::subjectPart( $subject, (string) $min, (string) $max );

		return $count >= (int) $min && $count <= (int) $max;
	}

	protected function matchesExact( string $subject, string|int $pattern ): bool {
		$CardPart    = substr( $subject, offset: 0, length: strlen( (string) $pattern ) );
		$patternPart = substr( (string) $pattern, offset: 0, length: strlen( $subject ) );

		return $patternPart === $CardPart;
	}

	private static function subjectPart( string $subject, string $min, string $max ): int {
		$maxLength = max( strlen( $min ), strlen( $max ) );

		return (int) substr( $subject, offset: 0, length: $maxLength );
	}
}
