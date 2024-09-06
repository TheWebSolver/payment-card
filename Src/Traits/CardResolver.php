<?php
/**
 * Resolves payment card type.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Matcher;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

trait CardResolver {
	/** @var Card[] */
	private array $registeredCards;

	public function setCardTypes( Card $card, Card ...$cards ): void {
		$this->registeredCards = array( $card, ...$cards );
	}

	/** @throws LogicException When cards not registered and `$registeredOnly` is `true`. */
	public function resolveCardFromNumber( string|int $number, bool $registeredOnly = false ): ?Card {
		$registered = $this->registeredCards ?? array();
		$cards      = $registeredOnly ? $registered : array( ...PaymentCard::cases(), ...$registered );

		if ( empty( $cards ) ) {
			throw new LogicException(
				sprintf( 'Payment Cards not registered to resolve from the given card number "%s".', $number )
			);
		}

		$maxLength = 0;
		$resolved  = null;

		foreach ( $cards as $card ) {
			if ( ! $card->isNumberValid( $number, withLuhnAlgorithm: true ) ) {
				continue;
			}

			[ $currentLength, $cardRange ] = $this->getMatchedIdRange( $card, (string) $number );

			if ( $maxLength < $currentLength ) {
				$maxLength = $currentLength;
				$resolved  = PaymentCard::getAltCardFrom( $cardRange, $card ) ?? $card;
			}
		}

		return $resolved;
	}

	/** @return array{0:int,1:int|int[]} */
	private function getMatchedIdRange( Card $card, string $number ): array {
		$maxLength = 0;
		$cardRange = 0;

		foreach ( $card->getIdRange() as $range ) {
			if ( ! Matcher::matchesIdRangeWith( $range, $number ) ) {
				continue;
			}

			$currentLength = is_array( $range )
				? (int) min( strlen( (string) $range[0] ), strlen( (string) $range[1] ) )
				: strlen( (string) $range );

			if ( $currentLength > $maxLength ) {
				$maxLength = $currentLength;
				$cardRange = $range;
			}
		}

		return array( $maxLength, $cardRange );
	}
}
