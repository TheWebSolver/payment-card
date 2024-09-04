<?php
/**
 * Generates regex based on Payment Card number length.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use OutOfBoundsException;
use TheWebSolver\Codegarage\PaymentCard\CardInterface;

trait RegexGenerator {
	/**
	 * @return string[]
	 * @throws OutOfBoundsException When Payment Card length is less than CardInterface::GAP_CHECKSUM.
	 */
	private function getDefaultRegex( int $size ): array {
		$checksum = self::ensureMinimumSizeProvided( $size );
		$pattern  = CardInterface::GAP_DEFAULT_PATTERN;

		if ( $size === $checksum ) {
			return array( "/$pattern/", CardInterface::GAP_HOLDERS );
		}

		$additional = $size - CardInterface::GAP_CHECKSUM;

		return in_array( $size, haystack: range( 13, 16 ), strict: true )
			? array( "/{$pattern}(\d{{$additional}})/", CardInterface::GAP_HOLDERS . ' $4' )
			: array( "/{$pattern}(\d{4})(\d{{$additional}})/", CardInterface::GAP_HOLDERS . ' $4 $5' );
	}

	/**
	 * @return string[]
	 * @throws OutOfBoundsException When Payment Card length is less than CardInterface::GAP_CHECKSUM.
	 */
	private function getAltRegex( int $size ): array {
		self::ensureMinimumSizeProvided( $size );

		return array(
			sprintf( '/' . CardInterface::GAP_ALT_PATTERN . '/', $size % 10 ),
			CardInterface::GAP_HOLDERS,
		);
	}

	private static function ensureMinimumSizeProvided( int $size ): int {
		return $size >= ( $checksum = CardInterface::GAP_CHECKSUM )
			? $checksum
			: throw new OutOfBoundsException(
				sprintf( 'Payment Card must be at least %s characters long.', $checksum )
			);
	}
}
