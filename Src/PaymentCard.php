<?php
/**
 * Known Payment Card types.
 *
 * @package TheWebSolver\Codegarage\Validation
 *
 * @phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.ArrayItemNoNewLine
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TypeError;
use TheWebSolver\Codegarage\PaymentCard\Traits\Matcher;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;
use TheWebSolver\Codegarage\PaymentCard\Traits\ForbidSetters;
use TheWebSolver\Codegarage\PaymentCard\CardInterface as Card;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexGenerator;

/**
 * @link https://en.wikipedia.org/wiki/Payment_card_number#Issuer_identification_number_(IIN)
 * @see ../Resource/paymentCards.json
 */
enum PaymentCard: string implements Card {
	use Validator, ForbidSetters, RegexGenerator, Matcher;

	case AmericanExpress = 'american-express';
	case DinersClub      = 'diners-club'; // ↓ Order matters. Can be resolved as Mastercard.
	case Mastercard      = 'mastercard';
	case Troy            = 'troy';        // ↓ Order matters. Can be resolved as Discover.
	case Discover        = 'discover';
	case UnionPay        = 'unionpay';
	case Maestro         = 'maestro';
	case Visa            = 'visa';
	case Jcb             = 'jcb';
	case Mir             = 'mir';

	public function jsonKey(): string {
		return match ( $this ) {
			default               => $this->value,
			self::AmericanExpress => 'americanExpress',
			self::DinersClub      => 'dinersClub',
		};
	}

	public function getType(): string {
		try {
			return $this->fromFactory()->getType();
		} catch ( TypeError ) {
			return CardFactory::CREDIT_CARD;
		}
	}

	public function needsLuhnCheck(): bool {
		try {
			return $this->fromFactory()->needsLuhnCheck();
		} catch ( TypeError ) {
			return true;
		}
	}

	public function getName(): string {
		try {
			return $this->fromFactory()->getName();
		} catch ( TypeError ) {
			return match ( $this ) {
				self::AmericanExpress => 'American Express',
				self::DinersClub      => 'Diners Club',
				self::Mastercard      => 'Mastercard',
				self::Discover        => 'Discover',
				self::UnionPay        => 'UnionPay',
				self::Maestro         => 'Maestro',
				self::Visa            => 'Visa',
				self::Troy            => 'Troy',
				self::Jcb             => 'JCB',
				self::Mir             => 'Mir'
			};
		}
	}

	public function getAlias(): string {
		try {
			return $this->fromFactory()->getAlias();
		} catch ( TypeError ) {
			return $this->value;
		}
	}

	public function getBreakpoint(): array {
		try {
			return $this->fromFactory()->getBreakpoint();
		} catch ( TypeError ) {
			return match ( $this ) {
				self::DinersClub,
				self::AmericanExpress => array( 4, 10 ),
				default               => array( 4, 8, 12 ),
			};
		}
	}

	public function getCode(): array {
		try {
			return $this->fromFactory()->getCode();
		} catch ( TypeError ) {
			return match ( $this ) {
				self::Maestro,
				self::Mastercard      => array( 'CVC', 3 ),
				self::AmericanExpress => array( 'CID', 4 ),
				self::Discover        => array( 'CID', 3 ),
				self::UnionPay        => array( 'CVN', 3 ),
				self::Mir             => array( 'CVP2', 3 ),
				default               => array( 'CVV', 3 )
			};
		}
	}

	public function getLength(): array {
		try {
			return $this->fromFactory()->getLength();
		} catch ( TypeError ) {
			return match ( $this ) {
				self::Discover, self::Jcb,
				self::Mir, self::UnionPay    => array( array( 16, 19 ) ),
				self::Troy, self::Mastercard => array( 16 ),
				self::AmericanExpress        => array( 15 ),
				self::Maestro                => array( array( 12, 19 ) ),
				self::Visa                   => array( 13, 16, 19 ),
				self::DinersClub             => array(
					/* US & Canada */   16,
					/* International */ array( 14, 19 ),
				),
			};
		}
	}

	public function getIdRange(): array {
		try {
			return $this->fromFactory()->getIdRange();
		} catch ( TypeError ) {
			return match ( $this ) {
				self::Jcb             => array( array( 3528, 3589 ) ),
				self::DinersClub      => array(
					/* MasterCard: US & Canada */ 55,
					/* International */           30, 36, 38, 39,
				),
				self::Discover        => array(
					/* UnionPay: China */ array( 622126, 622925 ),
					/* International */   6011, array( 644, 649 ), 65,
				),
				self::Maestro         => array(
					/* UK */            6759, 676770, 676774,
				/* International */ 5018, 5020, 5038, 5893, 6304, 6759, 6761, 6762, 6763,
				),
				self::Troy            => array(
					/* Discover: US */  65,
					/* International */ 9792,
				),
				self::Mir             => array( array( 2200, 2204 ) ),
				self::AmericanExpress => array( 34, 37 ),
				self::Visa            => array( 4 ),
				self::Mastercard      => array( array( 51, 55 ), array( 2221, 2720 ) ),
				self::UnionPay        => array( 62 ),
			};//end match
		}//end try
	}

	public function format( string|int $cardNumber ): string {
		$length                    = strlen( (string) $cardNumber );
		[ $pattern, $replacement ] = $this->getBreakpointRegex( cardSize: $length );

		return preg_replace( $pattern, $replacement, (string) $cardNumber )
			?? Asserter::formattingFailed( (string) $cardNumber );
		;
	}

	/** @param int|int[] $range */
	public function getPartneredCardFrom( int|array $range ): ?self {
		return match ( $this ) {
			default          => null,
			self::DinersClub => 55 === $range ? self::Mastercard : null,
			self::Troy       => 65 === $range ? self::Discover : null,
			self::Discover   => $this->isUnionPay( $range ) ? self::UnionPay : null,
		};
	}

	/** @param int|int[] $range */
	public static function maybeGetPartneredCard( int|array $range, Card $card ): Card {
		return ! $card instanceof self ? $card : ( $card->getPartneredCardFrom( $range ) ?? $card );
	}

	/** @return array{length:int,range:int|int[]} */
	public static function getMatchedIdRange( Card $card, string $number ): array {
		$length = 0;
		$range = 0;

		foreach ( $card->getIdRange() as $ranges ) {
			if ( ! PaymentCard::matchesIdRangeWith( $ranges, $number ) ) {
				continue;
			}

			$currentLength = is_array( $ranges )
				? (int) min( strlen( (string) $ranges[0] ), strlen( (string) $ranges[1] ) )
				: strlen( (string) $ranges );

			if ( $length < $currentLength ) {
				$length = $currentLength;
				$range  = $ranges;
			}
		}

		return compact( 'length', 'range' );
	}

	private function fromFactory(): Card {
		return CardFactory::{$this->jsonKey()}();
	}

	/** @return string[] */
	private function getBreakpointRegex( int $cardSize ): array {
		return match ( $this ) {
			self::DinersClub,
			self::AmericanExpress => $this->getAltRegex( size: $cardSize ),
			default               => $this->getDefaultRegex( size: $cardSize ),
		};
	}

	/** @param int|int[] $range */
	private function isUnionPay( int|array $range ): bool {
		if ( ! is_array( $range ) ) {
			return false;
		}

		$idMatches = static fn( int $value ): bool => str_starts_with( (string) $value, needle: '622126' )
			|| str_starts_with( (string) $value, needle: '622925' );

		return ! empty( array_filter( array: $range, callback: $idMatches ) );
	}
}
