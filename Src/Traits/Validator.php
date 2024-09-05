<?php
/**
 * Payment Card Validation methods.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use InvalidArgumentException;
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
		return $this->matchesType( $subject )
			&& $this->matchesLengths( $subject )
			&& $this->matchesLuhnAlgorithm( $subject, shouldRun: $withLuhnAlgorithm )
			&& $this->matchesPatterns( $subject );
	}

	public function isCodeValid( mixed $subject ): bool {
		return ( is_string( $subject ) || is_int( $subject ) )
			&& strlen( (string) $subject ) === ( $this->getCode()[1] ?? false );
	}

	protected function matchesLengths( string $subject ): bool {
		foreach ( $this->getLength() as $length ) {
			if ( self::matchesCardNumberSize( $length, value: $subject ) ) {
				return true;
			}
		}

		return false;
	}

	protected function matchesLuhnAlgorithm( string $subject, bool $shouldRun ): bool {
		return $shouldRun && class_exists( '\\TheWebSolver\\Codegarage\\LuhnAlgorithm' )
			? \TheWebSolver\Codegarage\LuhnAlgorithm::validate( value: $subject )
			: true;
	}

	protected function matchesPatterns( string $subject ): bool {
		foreach ( $this->getPattern() as $pattern ) {
			if ( $this->matchesPattern( $pattern, $subject ) ) {
				return true;
			}
		}

		return false;
	}

	/** @param string|int|string[]|int[] $pattern */
	private function matchesPattern( string|int|array $pattern, string $subject ): bool {
		return is_array( $pattern )
		? $this->matchesBetween( $subject, ...$pattern )
		: $this->matchesExact( $subject, $pattern );
	}

	private function matchesBetween( string $subject, string|int $min, string|int $max ): bool {
		$count = $this->subjectPart( $subject, (string) $min, (string) $max );

		return $count >= (int) $min && $count <= (int) $max;
	}

	private function matchesExact( string $subject, string|int $pattern ): bool {
		$CardPart    = substr( $subject, offset: 0, length: strlen( (string) $pattern ) );
		$patternPart = substr( (string) $pattern, offset: 0, length: strlen( $subject ) );

		return $patternPart === $CardPart;
	}

	private function matchesType( mixed &$subject ): bool {
		return is_scalar( $subject ) && ( $subject = Asserter::normalize( (string) $subject ) );
	}

	private function matchesCardNumberSize( mixed $length, string $value ): bool {
		$count = strlen( $value );

		try {
			$size = Asserter::assertHasSize( $length );
		} catch ( InvalidArgumentException ) {
			$size = 0;
		}

		return ! is_array( $size )
			? $count === $size
			: ( $size[0] ?? 0 ) && ( $size[1] ?? 0 ) && ( $count >= $size[0] && $count <= $size[1] );
	}

	private function subjectPart( string $subject, string $min, string $max ): int {
		$maxLength = max( strlen( $min ), strlen( $max ) );

		return (int) substr( $subject, offset: 0, length: $maxLength );
	}
}
