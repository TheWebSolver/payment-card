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


	const INVALID_CODE_ARRAY_COUNT = '%s must be an array with "name" and "size" keys.';
	const INVALID_CODE_NAME        = '%s "name" must be an uppercase string of 3 characters.';
	const INVALID_CODE_SIZE        = '%s "size" must be a positive integer of 1 digit.';

	const INVALID_NUMERIC_VALUE        = '%s must be an array of integers or ranges represented as arrays.';
	const INVALID_NUMERIC_SINGLE_VALUE = '%s must be a positive integer.';
	const INVALID_NUMERIC_RANGE_COUNT  = '%s must have range with two items in an array.';
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
		( is_array( $value ) && count( $value ) === 2 ) || $this->failedFor( self::INVALID_CODE_ARRAY_COUNT );

		[$name, $size] = [ $value['name'] ?? null, $value['size'] ?? null ];

		( is_string( $name ) && ctype_upper( $name ) && strlen( $name ) === 3 )
			|| $this->failedFor( self::INVALID_CODE_NAME );

		( is_int( $size ) && $size > 0 && strlen( (string) $size ) === 1 )
			|| $this->failedFor( self::INVALID_CODE_SIZE );

		return true;
	}

	private function validateNumeric( mixed $value ): true {
		return is_array( $value )
			? array_walk( $value, $this->validateEveryNumericItem( ... ) )
			: $this->failedFor( self::INVALID_NUMERIC_VALUE );
	}

	private function validateEveryNumericItem( mixed $item ): void {
		if ( ! is_array( $item ) ) {
			( is_int( $item ) && $item > 0 ) || $this->failedFor( self::INVALID_NUMERIC_SINGLE_VALUE );

			return;
		}

		count( $item ) === 2 || $this->failedFor( self::INVALID_NUMERIC_RANGE_COUNT );

		array_walk( $item, $this->validateEveryNumericItem( ... ) );

		$item[0] < $item[1] || $this->failedFor( self::INVALID_NUMERIC_RANGE_VALUES );
	}

	private function failedFor( string $msg ): never {
		throw new ValidationFail( sprintf( $msg, $this->value ) );
	}
}
