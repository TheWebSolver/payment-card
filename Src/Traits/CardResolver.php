<?php
/**
 * Resolves payment card type.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TypeError;
use LogicException;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;
use TheWebSolver\Codegarage\PaymentCard\CardFactory as Factory;

/** @phpstan-import-type CardSchema from Factory */
trait CardResolver {
	/** @var Card[] */
	private array $cards;
	private bool $registeredOnly;

	private function setCards( Card $card, Card ...$cards ): void {
		$this->cards = array( $card, ...$cards );
	}

	private function withoutDefaults(): static {
		$this->registeredOnly = true;

		return $this;
	}

	/**
	 * @param string|CardSchema $data
	 * @throws TypeError When content parsed from $data does not match the `CardSchema`.
	 */
	private function registerCardsFromPayload( string|array $data ): void {
		$this->cards = ( new Factory() )->withPayload( $data )->createCards( preserveKeys: false );
	}

	/** @return Card[] */
	private function getCards(): array {
		$cards = $this->cards ?? array();

		return ( $this->registeredOnly ?? false ) ? $cards : array( ...PaymentCard::cases(), ...$cards );
	}

	/** @return CardSchema[] */
	private function getCardsContent(): array {
		return array_map( array: $this->getCards(), callback: $this->getCardContent( ... ) );
	}

	/** @return CardSchema */
	private function getCardContent( Card $card ): array {
		$data = array();

		foreach ( Factory::CARD_SCHEMA as $key => $schema ) {
			if ( str_ends_with( haystack: $key, needle: '?' ) ) {
				continue;
			}

			$getterMethod = 'get' . ucwords( $key );
			$data[ $key ] = $card->{$getterMethod}();
		}

		/** @var CardSchema */
		return $data;
	}

	/** @throws LogicException When cards not registered and `CardResolver::withoutDefaults()` used. */
	private function resolveCardFromNumber( string|int $number ): ?Card {
		if ( empty( $cards = $this->getCards() ) ) {
			throw new LogicException(
				sprintf( 'Payment Cards not registered. Impossible to resolve card number: "%s".', $number )
			);
		}

		$maxLength = 0;
		$resolved  = null;

		foreach ( $cards as $card ) {
			if ( ! $card->isNumberValid( $number ) ) {
				continue;
			}

			[ 'length' => $length, 'range' => $range ] = PaymentCard::getMatchedIdRange( $card, (string) $number );

			if ( $maxLength < $length ) {
				$maxLength = $length;
				$resolved  = PaymentCard::maybeGetPartneredCard( $range, $card );
			}
		}

		return $resolved;
	}
}
