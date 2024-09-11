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
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\PaymentCard;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;

/** @phpstan-import-type CardSchema from CardFactory */
trait CardResolver {
	/** @var Card[] */
	private array $registeredCards;

	private function setCards( Card $card, Card ...$cards ): void {
		$this->registeredCards = array( $card, ...$cards );
	}

	/**
	 * @param string|CardSchema $payload
	 * @throws TypeError When content resolved from $payload does not match the `CardSchema`.
	 */
	private function setCardsFromContent( string|array $payload ): void {
		$this->registeredCards = ( new CardFactory() )
			->withPayload( data: $payload )
			->createCards( preserveKeys: false );
	}

	/** @return Card[] */
	private function getCards( bool $registeredOnly = false ): array {
		$registered = $this->registeredCards ?? array();

		return $registeredOnly ? $registered : array( ...PaymentCard::cases(), ...$registered );
	}

	/** @return CardSchema[] */
	private function getCardsContent( bool $registeredOnly = false ): array {
		return array_map( array: $this->getCards( $registeredOnly ), callback: $this->getCardContent( ... ) );
	}

	/** @return CardSchema */
	private function getCardContent( Card $card ): array {
		$data = array();

		foreach ( CardFactory::CARD_SCHEMA as $key => $schema ) {
			if ( str_ends_with( haystack: $key, needle: '?' ) ) {
				continue;
			}

			$getterMethod = 'get' . ucwords( $key );
			$data[ $key ] = $card->{$getterMethod}();
		}

		/** @var CardSchema */
		return $data;
	}

	/** @throws LogicException When cards not registered and `$registeredOnly` is `true`. */
	private function resolveCardFromNumber( string|int $number, bool $registeredOnly = false ): ?Card {
		if ( empty( $cards = $this->getCards( registeredOnly: $registeredOnly ) ) ) {
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

			[ $currentLength, $currentRange ] = $this->getMatchedIdRange( $card, (string) $number );

			if ( $maxLength < $currentLength ) {
				$maxLength = $currentLength;
				$resolved  = PaymentCard::maybeGetPartneredCard( $currentRange, $card );
			}
		}

		return $resolved;
	}

	/** @return array{0:int,1:int|int[]} */
	private function getMatchedIdRange( Card $card, string $number ): array {
		$maxLength = 0;
		$cardRange = 0;

		foreach ( $card->getIdRange() as $range ) {
			if ( ! PaymentCard::matchesIdRangeWith( $range, $number ) ) {
				continue;
			}

			$currentLength = is_array( $range )
				? (int) min( strlen( (string) $range[0] ), strlen( (string) $range[1] ) )
				: strlen( (string) $range );

			if ( $maxLength < $currentLength ) {
				$maxLength = $currentLength;
				$cardRange = $range;
			}
		}

		return array( $maxLength, $cardRange );
	}
}
