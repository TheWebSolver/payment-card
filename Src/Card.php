<?php
/**
 * The Payment Card helper.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use RuntimeException;
use InvalidArgumentException;

class Card {
	public const CREDIT = 'Credit Card';
	public const DEBIT  = 'Debit Card';

	public const ALLOWED_PATTERN = '/[^0-9]/';

	public const NEEDS_ONE_ELEMENT       = '%1$s %2$s must have atleast one element.';
	public const NEEDS_TWO_ELEMENTS      = '%1$s %2$s value must only be of two elements in an array.';
	public const NEEDS_POSITIVE_INT      = '%1$s %2$s minimum value must be a positive integer.';
	public const NEEDS_MIN_LESS_THAN_MAX = '%1$s %2$s minimum value must be less than maximum value.';
	public const NEEDS_STRING_OR_INT     = '%1$s %2$s must be between [0-9] as either a "string" or an "int" type. "%3$s" type given.';

	public const INVALID_FORMATTING = '%1$s "%2$s" could not be formatted according to the provided gap.';

	private static string $attribute;
	private static string $cardType;

	public function setType( string $name ): void {
		self::$cardType ??= $name;
	}

	/**
	 * @param mixed[] $value
	 * @return (int|int[])[]
	 */
	public function resolveSizeWith( array $value, string $forType ) {
		self::assertNotEmpty( $value );
		self::isProcessing( $forType );

		array_walk( array: $value, callback: self::assertHasSize( ... ) );

		/** @var (int|int[])[] */
		return $value;
	}

	public static function isProcessing( string $name ): void {
		self::$attribute = $name;
	}

	public static function normalize( string $cardNumber ): string {
		return preg_replace( pattern: self::ALLOWED_PATTERN, replacement: '', subject: $cardNumber ) ?? '';
	}

	public static function parsePropNameFrom( string $getterSetter ): string {
		return lcfirst( string: substr( string: $getterSetter, /* set/get */ offset: 3 ) );
	}

	/**
	 * @param mixed[] $value
	 * @throws InvalidArgumentException When $value does not have any element.
	 */
	public static function assertNotEmpty( array $value ): true {
		return ! empty( $value ) ?: self::assertionFailed( self::NEEDS_ONE_ELEMENT );
	}

	/**
	 * @return int|int[]
	 * @throws InvalidArgumentException When given size is an array without exactly 2 elements.
	 */
	public static function assertHasSize( mixed &$size ): int|array {
		return match ( true ) {
			default              => self::assertionFailed( self::NEEDS_TWO_ELEMENTS ),
			! is_array( $size )  => $size = self::assertSingleSize( $size ),
			count( $size ) === 2 => self::assertPositiveAndValid(
				$size = array_map( callback: self::assertSingleSize( ... ), array: $size )
			),
		};
	}

	/**
	 * @param int[] $sizes
	 * @return int[]
	 * @throws InvalidArgumentException When more than 2 sizes given if array size, min is non-positive
	 *                                  integer value or min value is greater than max value.
	 */
	public static function assertPositiveAndValid( array $sizes ): array {
		$errorMsg = match ( true ) {
			default                                      => null,
			$sizes[0] < 0                                => self::NEEDS_POSITIVE_INT,
			isset( $sizes[1] ) && $sizes[0] >= $sizes[1] => self::NEEDS_MIN_LESS_THAN_MAX,
		};

		return ! $errorMsg ? $sizes : self::assertionFailed( $errorMsg );
	}

	/** @throws InvalidArgumentException When given size is neither a string nor an integer. */
	public static function assertSingleSize( mixed $size ): int {
		return is_int( $size ) || is_string( $size )
			? self::assertPositiveAndValid( array( (int) $size ) )[0]
			: self::assertionFailed( self::NEEDS_STRING_OR_INT, get_debug_type( $size ) );
	}

	/**
	 * @param string     $message Error message.
	 * @param string|int ...$args Replacement args.
	 * @throws InvalidArgumentException With given msg.
	 */
	public static function assertionFailed( string $message, string|int ...$args ): never {
		throw new InvalidArgumentException( sprintf( $message, self::$cardType, self::$attribute, ...$args ) );
	}

	/** @throws RuntimeException When formatting fails. */
	public static function formattingFailed( string $cardNumber ): never {
		throw new RuntimeException(
			sprintf( self::INVALID_FORMATTING, self::$cardType ?? 'Payment Card', $cardNumber )
		);
	}
}
