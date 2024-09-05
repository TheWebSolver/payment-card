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

use TheWebSolver\Codegarage\PaymentCard\CardInterface;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;
use TheWebSolver\Codegarage\PaymentCard\Traits\ForbidSetters;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexGenerator;

/** @link https://en.wikipedia.org/wiki/Payment_card_number#Issuer_identification_number_(IIN) */
enum PaymentCard: string implements CardInterface {
	use Validator, ForbidSetters, RegexGenerator;

	case AmericanExpress = 'american-express';
	case DinersClub      = 'diners-club';
	case Mastercard      = 'mastercard';
	case Discover        = 'discover';
	case UnionPay        = 'unionpay';
	case Maestro         = 'maestro';
	case Visa            = 'visa';
	case Troy            = 'troy';
	case Jcb             = 'jcb';
	case Mir             = 'mir';

	public function getName(): string {
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

	public function getAlias(): string {
		return $this->value;
	}

	public function getGap(): array {
		return match ( $this ) {
			self::DinersClub,
			self::AmericanExpress => array( 4, 10 ),
			default               => array( 4, 8, 12 ),
		};
	}

	public function getCode(): array {
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

	public function getLength(): array {
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

	public function getPattern(): array {
		return match ( $this ) {
			self::Jcb             => array( array( 3528, 3589 ) ),
			self::DinersClub      => array(
				/* US & Canada */   55,
				/* International */ 30, 36, 38, 39,
			),
			self::Discover        => array(
				/* China UnionPay */ array( 622126, 622925 ),
				/* International */  6011, array( 644, 649 ), 65,
			),
			self::Maestro         => array(
				/* UK */             6759, 676770, 676774,
				/* International */ 5018, 5020, 5038, 5893, 6304, 6759, 6761, 6762, 6763,
			),
			self::Troy            =>array(
				/* Discover */      65,
				/* International */ 9792,
			),
			self::Mir             => array( array( 2200, 2204 ) ),
			self::AmericanExpress => array( 34, 37 ),
			self::Visa            => array( 4 ),
			self::Mastercard      => array( array( 51, 55 ), array( 2221, 2720 ) ),
			self::UnionPay        => array( 62 ),
		};//end match
	}

	public function format( string|int $cardNumber ): string {
		$length                    = strlen( (string) $cardNumber );
		[ $pattern, $replacement ] = $this->getGapRegex( $length );

		return preg_replace( $pattern, $replacement, (string) $cardNumber )
			?? CardHandler::formattingFailed( (string) $cardNumber );
		;
	}

	/** @return string[] */
	private function getGapRegex( int $cardSize ): array {
		return match ( $this ) {
			self::DinersClub,
			self::AmericanExpress => $this->getAltRegex( size: $cardSize ),
			default               => $this->getDefaultRegex( size: $cardSize ),
		};
	}
}
