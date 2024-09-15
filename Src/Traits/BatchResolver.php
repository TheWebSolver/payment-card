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
	private ?Card $resolved = null;

	/** @return array<string,string> */
	private function getCoveredCards(): array {
		return $this->coveredCards ?? array();
	}

	private function resetCoveredCards(): void {
		$this->coveredCards = array();
	}

	private function resolveCardFromNumberIn( Generator $batch, string|int $number ): ?Card {
		$shouldLoadNextCard = true;

		do {
				/** @var Card*/
				$card                                    = $batch->current();
				$this->coveredCards[ $card->getAlias() ] = 'invalid';

			if ( $this->resolveCardRange( $card, $number ) ) {
				$shouldLoadNextCard                      = false;
				$this->coveredCards[ $card->getAlias() ] = 'valid';
			}

			$batch->next();
		} while ( $shouldLoadNextCard && $batch->valid() );

		$resolved = $this->resolved ?? null;

		unset( $this->resolved );

		return $resolved;
	}

	private function resolveCardRange( Card $card, string|int $number ): ?Card {
		[ 'length' => $length, 'range' => $range ] = PaymentCard::getMatchedIdRange( $card, (string) $number );

		$maxLength = 0;

		if ( $maxLength < $length ) {
			$maxLength       = $length;
			$this->resolved  = PaymentCard::maybeGetPartneredCard( $range, $card );
		}

		return $this->resolved ?? null;
	}
}
