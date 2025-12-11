<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Enums;

use TheWebSolver\Codegarage\Scraper\Error\ValidationFail;

enum Card: string {
	case Name       = 'name';
	case Alias      = 'alias';
	case Breakpoint = 'breakpoint';
	case Length     = 'length';
	case IINRange   = 'id-range';
	case Code       = 'code';
	case Status     = 'status';
	case Validator  = 'validator';

	const INVALID_CODE_ARRAY_COUNT = self::Code->value . ' must be an array with "name" and "size" keys.';
	const INVALID_CODE_NAME        = self::Code->value . ' "name" must be an uppercase string of 3 characters.';
	const INVALID_CODE_SIZE        = self::Code->value . ' "size" must be a positive integer of 1 digit.';

	const INVALID_NUMERIC_VALUE        = '%s must be a positive integer.';
	const INVALID_NUMERIC_RANGE_COUNT  = '%s must have range with two elements in an array.';
	const INVALID_NUMERIC_RANGE_VALUES = '%s range\'s starting value must be less than ending value.';

	/** @throws ValidationFail When value assertion fails. */
	public function validate( mixed $value ): bool {
		return match ( $this ) {
			self::Code                                     => $this->validateCode( $value ),
			self::Status                                   => is_bool( $value ),
			self::Name, self::Alias, self::Validator       => is_string( $value ),
			self::Breakpoint, self::Length, self::IINRange => $this->validateNumeric( $value ),
		};
	}

	private function validateCode( mixed $value ): true {
		if ( ! is_array( $value ) || count( $value ) !== 2 ) {
			throw new ValidationFail( self::INVALID_CODE_ARRAY_COUNT );
		}

		$name = $value['name'] ?? null;
		$size = $value['size'] ?? null;

		if ( ! is_string( $name ) || ! ctype_upper( $name ) || 3 !== strlen( $name ) ) {
			throw new ValidationFail( self::INVALID_CODE_NAME );
		}

		if ( ! is_int( $size ) || $size <= 0 || 1 !== strlen( (string) $size ) ) {
			throw new ValidationFail( self::INVALID_CODE_SIZE );
		}

		return true;
	}

	private function validateNumeric( mixed $value ): true {
		if ( ! is_array( $value ) ) {
			return ( is_int( $value ) && $value > 0 )
				?: throw new ValidationFail( sprintf( self::INVALID_NUMERIC_VALUE, $this->value ) );
		}

		if ( count( $value ) !== 2 ) {
			throw new ValidationFail( sprintf( self::INVALID_NUMERIC_RANGE_COUNT, $this->value ) );
		}

		array_walk( $value, $this->validateNumeric( ... ) );

		return $value[0] < $value[1]
			?: throw new ValidationFail( sprintf( self::INVALID_NUMERIC_RANGE_VALUES, $this->value ) );
	}
}
