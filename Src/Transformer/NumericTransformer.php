<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,list<int|list<int>>> */
class NumericTransformer implements Transformer {
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<bracketRange>[\[]+[\d, ]+[\]])(?<maybeBracketOpen>[\[]? ?)(?<dashRange>[\d]+[\-\–]+[\d]+)(?<digits>[\d]+))';

	/** @placeholder `%s:` Given element type.  */
	final public const INVALID_ELEMENT = 'Invalid element type provided to transform numeric value. Expected string type, "%s" type given.';
	/** @placeholder: `%s` The given string to convert to integer. */
	final public const NOT_A_NUMERIC_VALUE = 'Impossible to transform non-numeric source: "%s" to integer.';

	/** @return list<int|list<int>> */
	public static function transformToNumber( string $source ): array {
		$extracted = self::extractNumericValues( $source );

		array_walk( $extracted, self::walkExtractedValue( ... ) );

		return $extracted;
	}

	/**
	 * @return int|list<int>
	 * @param-out int|list<int> $value
	 */
	public static function walkExtractedValue( string &$value, mixed $key ): int|array {
		return $value = match ( true ) {
			str_starts_with( $value, '[' ) => self::rangeToDigits( self::extractNumericValues( $value ) ),
			str_contains( $value, '-' )    => self::rangeToDigits( explode( '-', $value, limit: 2 ) ),
			str_contains( $value, '–' )    => self::rangeToDigits( explode( '–', $value, limit: 2 ) ),
			default                        => self::maybeToDigit( $value )
		};
	}

	/**
	 * @return list<string>
	 * @throws ScraperError When pattern match fails.
	 */
	public static function extractNumericValues( string $source ): array {
		preg_match_all( $pattern = self::getRegexPattern(), $source, $matched )
			|| ScraperError::patternMismatch( "Card's numeric values", $pattern, $source );

		return $matched['value'];
	}

	public static function getRegexPattern(): string {
		$define = self::PATTERN_DEFINITION;

		return "/{$define}(?&maybeBracketOpen)?(?<value>(?&bracketRange)|(?&dashRange)|(?&digits))/";
	}

	public static function maybeToDigit( string $value ): int {
		return abs( intval( $value ) );
	}

	public function transform( string|array|DOMElement $element, object $scope ): array {
		return is_string( $element )
			? self::transformToNumber( $element )
			: throw ScraperError::trigger( self::INVALID_ELEMENT, get_debug_type( $element ) );
	}

	/**
	 * @param list<string> $range
	 * @return list<int>
	 */
	private static function rangeToDigits( array $range ): array {
		array_walk( $range, self::walkExtractedValue( ... ) );

		return $range;
	}
}
