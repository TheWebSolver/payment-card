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
	abstract public function getIdRange(): array;
	abstract public function getCode(): array;

	public function isNumberValid( mixed $subject, bool $withLuhnAlgorithm = true ): bool {
		return $this->matchesType( $subject )
			&& $this->matchesLengths( $subject )
			&& $this->matchesLuhnAlgorithm( $subject, shouldRun: $withLuhnAlgorithm )
			&& $this->matchesIdRanges( $subject );
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

	protected function matchesIdRanges( string $subject ): bool {
		foreach ( $this->getIdRange() as $range ) {
			if ( $this->matchesIdRange( $range, $subject ) ) {
				return true;
			}
		}

		return false;
	}

	private function matchesIdRange( mixed $range, string $subject ): bool {
		try {
			$range = Asserter::assertHasSize( $range );
		} catch ( InvalidArgumentException ) {
			return false;
		}

		return is_array( $range )
			? $this->matchesBetween( $subject, ...$range )
			: $this->matchesExact( $subject, $range );
	}

	private function matchesBetween( string $subject, int $min, int $max ): bool {
		$count = $this->subjectPart( $subject, (string) $min, (string) $max );

		return $count >= $min && $count <= $max;
	}

	private function matchesExact( string $subject, int $range ): bool {
		$CardPart    = substr( $subject, offset: 0, length: strlen( (string) $range ) );
		$rangePart = substr( (string) $range, offset: 0, length: strlen( $subject ) );

		return $rangePart === $CardPart;
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
