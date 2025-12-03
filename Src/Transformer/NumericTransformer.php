<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,list<int|string|list<int|string>>> */
class NumericTransformer implements Transformer {
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<bracketRange>[\[]+[\d, ]+[\]])(?<dashRange>[\d]+[\-\–]+[\d]+)(?<digits>[\d]+))';

	/** @placeholder `%s:` Given element type.  */
	final public const INVALID_ELEMENT = 'Invalid element type provided to transform numeric value. Expected string type, "%s" type given.';
	/** @placeholder: `%s` The given string to convert to integer. */
	final public const NOT_A_NUMERIC_VALUE = 'Impossible to transform non-numeric source: "%s" to integer.';

	/** @return list<int|string|list<int|string>> */
	public static function transformToNumber( string $source, bool $numericToInteger = true ): array {
		$extracted = self::extractNumericValues( $source );

		array_walk( $extracted, self::walkExtractedValue( ... ), $numericToInteger );

		return $extracted;
	}

	/** @param-out int|string|list<int|string> $value */
	public static function walkExtractedValue( string &$value, mixed $k, bool $toDigit = true ): void {
		$value = match ( true ) {
			str_starts_with( $value, '[' ) => self::rangeToDigits( self::extractNumericValues( $value ), $toDigit ),
			str_contains( $value, '-' )    => self::rangeToDigits( explode( '-', $value, limit: 2 ), $toDigit ),
			str_contains( $value, '–' )    => self::rangeToDigits( explode( '–', $value, limit: 2 ), $toDigit ),
			default                        => self::maybeToDigit( $value, $toDigit )
		};
	}

	/**
	 * @return list<string>
	 * @throws ScraperError When pattern match fails.
	 */
	public static function extractNumericValues( string $source ): array {
		preg_match_all( self::getRegexPattern(), $source, $matched )
			|| ScraperError::patternMismatch( "Card's numeric values", self::getRegexPattern(), $source );

		return $matched['value'];
	}

	public static function getRegexPattern(): string {
		$definedPattern = self::PATTERN_DEFINITION;
		$possiblePrefix = '?:[\[]? ?';

		return "/{$definedPattern}({$possiblePrefix})?(?<value>(?&bracketRange)|(?&dashRange)|(?&digits))/";
	}

	public static function maybeToDigit( string $value, bool $convert = true ): int|string {
		return $convert && ctype_digit( $value ) ? abs( intval( $value ) ) : $value;
	}

	public function __construct( private readonly bool $numericToInteger = true ) {}

	public function transform( string|array|DOMElement $element, object $scope ): array {
		return is_string( $element )
			? self::transformToNumber( $element, $this->numericToInteger )
			: throw ScraperError::trigger( self::INVALID_ELEMENT, get_debug_type( $element ) );
	}

	/**
	 * @param list<string> $range
	 * @return list<int|string>
	 */
	private static function rangeToDigits( array $range, bool $toDigit ): array {
		array_walk( $range, self::walkExtractedValue( ... ), $toDigit );

		return $range;
	}
}
