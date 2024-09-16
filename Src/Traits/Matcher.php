<?php
/**
 * Matches Payment Card ID data.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use InvalidArgumentException;
use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait Matcher {
	public static function matchesAllowedPattern( mixed &$value ): bool {
		return is_scalar( $value ) && ( $value = Asserter::normalize( (string) $value ) );
	}

	public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
		return $shouldRun && class_exists( '\\TheWebSolver\\Codegarage\\LuhnAlgorithm' )
			? \TheWebSolver\Codegarage\LuhnAlgorithm::validate( value: $value )
			: true;
	}

	/** @param mixed[] $sizes */
	public static function matchesLength( array $sizes, string $value ): bool {
		foreach ( $sizes as $size ) {
			if ( self::matchesLengthWith( $size, $value ) ) {
				return true;
			}
		}

		return false;
	}

	public static function matchesLengthWith( mixed $size, string $value ): bool {
		try {
			$size = Asserter::assertHasSize( $size );
		} catch ( InvalidArgumentException ) {
			return false;
		}

		$count = strlen( $value );

		return ! is_array( $size )
			? $size && ( $count === $size )
			: ( $size[0] ?? 0 ) && ( $size[1] ?? 0 ) && ( $count >= $size[0] && $count <= $size[1] );
	}

	/** @param mixed[] $sizes */
	public static function matchesIdRange( array $sizes, string $value ): bool {
		foreach ( $sizes as $range ) {
			if ( self::matchesIdRangeWith( $range, $value ) ) {
				return true;
			}
		}

		return false;
	}

	public static function matchesIdRangeWith( mixed $size, string $value ): bool {
		try {
			$size = Asserter::assertHasSize( $size );
		} catch ( InvalidArgumentException ) {
			return false;
		}

		return is_array( $size )
			? self::matchesRangeInBetween( $value, ...$size )
			: self::matchesRangeExactNumber( $value, range: (string) $size );
	}

	private static function matchesRangeInBetween( string $value, int $minRange, int $maxRange ): bool {
		$beginningNumbers = self::getBeginningNumbers( $value, (string) $minRange, (string) $maxRange );

		// Eg: $minRange: 98; $maxRange: 101, $beginningNumbers: 100. Number is within range.
		return $beginningNumbers >= $minRange && $beginningNumbers <= $maxRange;
	}

	private static function matchesRangeExactNumber( string $value, string $range ): bool {
		$CardPart  = substr( $value, offset: 0, length: strlen( $range ) );
		$rangePart = substr( $range, offset: 0, length: strlen( $value ) );

		return $rangePart === $CardPart;
	}

	private static function getBeginningNumbers( string $value, string $min, string $max ): int {
		return (int) substr( $value, offset: 0, length: max( strlen( $min ), strlen( $max ) ) );
	}
}
