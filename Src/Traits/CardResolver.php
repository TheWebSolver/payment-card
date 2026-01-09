<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\CardInterface;

trait CardResolver {
	/** @var string[] */
	private array $coveredCards;

	/** @return string[] */
	public function getCoveredCardIndices(): array {
		return $this->coveredCards ?? [];
	}

	public function resetCoveredCardIndices(): void {
		$this->coveredCards = [];
	}

	/** @return ($exitOnResolve is true ? CardInterface|null : non-empty-list<CardInterface>|null) */
	private function resolve( string|int $cardNumber, CardFactory $factory, bool $exitOnResolve = true ): null|CardInterface|array {
		$resolvedCards = [];
		$allowedCards  = $factory->indicesToCreate;
		$generator     = $factory->lazyloadCardsBySentPayloadIndex();

		while ( $generator->valid() ) {
			$key                        = $generator->key();
			$needCardCreation           = ! $allowedCards || in_array( $key, $allowedCards, strict: true );
			$card                       = $generator->send( $needCardCreation );
			$status                     = ! $card ? 'disallowed' : ( $card->isNumberValid( $cardNumber ) ? 'valid' : 'invalid' );
			$this->coveredCards[ $key ] = $status;

			if ( $card && 'valid' === $status ) {
				if ( $exitOnResolve ) {
					$resolvedCards = $card;

					break;
				} else {
					$resolvedCards[] = $card;
				}
			}
		}

		return $resolvedCards ?: null;
	}
}
