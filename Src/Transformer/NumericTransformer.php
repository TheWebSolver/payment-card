<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,list<int|list<int>>> */
class NumericTransformer implements Transformer {
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<maybeBracketOpen>[\[]? ?)(?<valueSeparator>[,? ?])(?<bracketRange>[\[]+[\d, ]+[\]])(?<dashRange>[\d]+[\-\– ]+[\d]+)(?<digits>[\d]+))';

	/** @placeholder `%s:` Given element type.  */
	final public const INVALID_ELEMENT = 'Invalid element type provided to transform numeric value. Expected string type, "%s" type given.';

	/** @return int|list<int> */
	public static function mapExtractedValue( string $value ): int|array {
		return match ( true ) {
			str_starts_with( $value, '[' ) => array_map( self::toDigit( ... ), self::extractNumericValues( $value ) ),
			str_contains( $value, '-' )    => array_map( self::toDigit( ... ), explode( '-', $value, limit: 2 ) ),
			str_contains( $value, '–' )    => array_map( self::toDigit( ... ), explode( '–', $value, limit: 2 ) ),
			default                        => self::toDigit( $value )
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

		return "/{$define}(?&maybeBracketOpen)(?&valueSeparator)?(?<value>(?&bracketRange)|(?&dashRange)|(?&digits))/";
	}

	public static function toDigit( string $value ): int {
		return abs( intval( $value ) );
	}

	public function transform( string|array|DOMElement $element, object $scope ): array {
		return is_string( $element )
			? array_map( self::mapExtractedValue( ... ), self::extractNumericValues( $element ) )
			: throw new InvalidSource( sprintf( self::INVALID_ELEMENT, get_debug_type( $element ) ) );
	}
}
