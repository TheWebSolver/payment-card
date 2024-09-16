<?php
/**
 * Resolves payment card type in a batch.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use Generator;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

trait BatchResolver {
	/** @var array<string,string> */
	private array $coveredCards;

	/** @return array<string,string> */
	private function getCoveredCards(): array {
		return $this->coveredCards ?? array();
	}

	private function resetCoveredCards(): void {
		$this->coveredCards = array();
	}

	private function resolveCardFromNumberIn( Generator $batch, string|int $number ): ?Card {
		$shouldLoadNextCard = true;
		$length             = 0;
		$matches            = null;

		do {
			if ( ! ( $card = $batch->current() ) instanceof Card ) {
				$shouldLoadNextCard = false;

				continue;
			}

			PaymentCard::matchIdRange( $card, $number, $length, $matches );

			$shouldLoadNextCard                      = ! $matches;
			$this->coveredCards[ $card->getAlias() ] = ( $matches ? '' : 'in' ) . 'valid';

			$batch->next();
		} while ( $shouldLoadNextCard && $batch->valid() );

		return $matches;
	}
}
