<?php
/**
 * Payment Card Formatter with Gaps based on Regex pattern.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait RegexBasedFormatter {
	/** @var array{pattern:string,replacement:string,valid:int[],checksum:int} */
	public array $gap;

	/** @return int[] */
	public function getGap(): array {
		return $this->gap['valid'];
	}

	public function setGap( string|int $gap, string|int ...$gaps ): static {
		Asserter::isProcessing( name: 'gap' );

		$pattern = $replacement = '';
		$total   = $valid = array();
		$gaps    = array( $gap, ...$gaps );
		$first   = array_key_first( $gaps );

		foreach ( $gaps as $step => &$checksum ) {
			$valid[]      = $checksum = Asserter::assertSingleSize( $checksum );
			$count        = $first === $step ? $checksum : $checksum - (int) $gaps[ (int) $step - 1 ];
			$replacement .= $first === $step ? '$1' : ' $' . ( (int) $step + 1 );
			$pattern     .= '(\d{' . $count . '})';
		}

		$this->gap = compact( 'pattern', 'replacement', 'valid', 'checksum' );

		return $this;
	}

	public function format( string|int $cardNumber ): string {
		[ 'pattern'     => $pattern,
			'replacement' => $replacement,
			'valid'       => $validGaps,
			'checksum'    => $checksum ] = $this->gap;

		if ( $checksum < ( $length = strlen( (string) $cardNumber ) ) ) {
			$remaining    = $length - $checksum;
			$pattern     .= '(\d{' . $remaining . '})';
			$replacement .= ' $' . ( count( $validGaps ) + 1 );
		}

		return preg_replace( '/' . $pattern . '/', $replacement, (string) $cardNumber )
			?? Asserter::formattingFailed( (string) $cardNumber );
	}
}
