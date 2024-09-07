<?php
/**
 * Matches Payment Card ID data.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use InvalidArgumentException;
use TheWebSolver\Codegarage\PaymentCard\Asserter;

class Matcher {
	public static function matchesType( mixed &$value ): bool {
		return is_scalar( $value ) && ( $value = Asserter::normalize( (string) $value ) );
	}

	public static function matchesLuhnAlgorithm( string $value, bool $shouldRun = true ): bool {
		return $shouldRun && class_exists( '\\TheWebSolver\\Codegarage\\LuhnAlgorithm' )
			? \TheWebSolver\Codegarage\LuhnAlgorithm::validate( value: $value )
			: true;
	}

	/** @param mixed[] $sizes */
	public static function matchesLength( array $sizes, string $value ): bool {
		foreach ( $sizes as $length ) {
			if ( self::matchesLengthWith( $length, value: $value ) ) {
				return true;
			}
		}

		return false;
	}

	public static function matchesLengthWith( mixed $size, string $value ): bool {
		$count = strlen( $value );

		try {
			$size = Asserter::assertHasSize( $size );
		} catch ( InvalidArgumentException ) {
			$size = 0;
		}

		return ! is_array( $size )
			? $count === $size
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
			: self::matchesRangeExactNumber( $value, $size );
	}

	private static function matchesRangeInBetween( string $value, int $min, int $max ): bool {
		$count = self::subjectPart( $value, (string) $min, (string) $max );

		return $count >= $min && $count <= $max;
	}

	private static function matchesRangeExactNumber( string $value, int $range ): bool {
		$CardPart  = substr( $value, offset: 0, length: strlen( (string) $range ) );
		$rangePart = substr( (string) $range, offset: 0, length: strlen( $value ) );

		return $rangePart === $CardPart;
	}

	private static function subjectPart( string $value, string $min, string $max ): int {
		return (int) substr( $value, offset: 0, length: max( strlen( $min ), strlen( $max ) ) );
	}
}
