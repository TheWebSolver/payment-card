<?php
/**
 * Generates regex based on Payment Card number length.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use OutOfBoundsException;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

trait RegexGenerator {
	/**
	 * @return string[]
	 * @throws OutOfBoundsException When Payment Card length is less than Card::BREAKPOINT_CHECKSUM.
	 */
	private function getDefaultRegex( int $size ): array {
		$checksum  = self::ensureMinimumSizeProvided( $size );
		$pattern   = Card::BREAKPOINT_DEFAULT_PATTERN;
		$nextRange = array(
			'start' => 13,
			'end'   => 16,
		);

		if ( $size === $checksum ) {
			return array( '/' . $pattern . '/', Card::BREAKPOINT_HOLDERS );
		}

		return in_array( $size, haystack: range( ...$nextRange ), strict: true )
			? array(
				'/' . $pattern . '(\d{' . ( $size - Card::BREAKPOINT_CHECKSUM ) . '})/',
				Card::BREAKPOINT_HOLDERS . ' $4',
			)
			: array(
				'/' . $pattern . '(\d{4})(\d{' . ( $size - $nextRange['end'] ) . '})/',
				Card::BREAKPOINT_HOLDERS . ' $4 $5',
			);
	}

	/**
	 * @return string[]
	 * @throws OutOfBoundsException When Payment Card length is less than Card::BREAKPOINT_CHECKSUM.
	 */
	private function getAltRegex( int $size ): array {
		self::ensureMinimumSizeProvided( $size );

		return array(
			sprintf( '/' . Card::BREAKPOINT_ALT_PATTERN . '/', $size - 10 ),
			Card::BREAKPOINT_HOLDERS,
		);
	}

	private static function ensureMinimumSizeProvided( int $size ): int {
		return $size >= ( $checksum = Card::BREAKPOINT_CHECKSUM )
			? $checksum
			: throw new OutOfBoundsException(
				sprintf( 'Payment Card must be at least %s characters long.', $checksum )
			);
	}
}
