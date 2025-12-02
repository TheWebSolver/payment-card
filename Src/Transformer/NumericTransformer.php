<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,list<int|string|list<int|string>>> */
class NumericTransformer implements Transformer {
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<bracketRange>[\[]+[\d, ]+[\]])(?<dashRange>[\d]+[\-\–]+[\d]+)(?<digits>[\d]+))';

	/** @placeholder: `%s` The given string to convert to integer. */
	final public const NOT_A_NUMERIC_VALUE = 'Impossible to transform non-numeric source: "%s" to integer.';
	/** @placeholder: `1:` Regex pattern to extract numeric values from given string, `2:` The given string. */
	final public const INVALID_PATTERN_FOR_NUMERIC_VALUE = 'Card details numeric value only supports defined pattern "%1$s". Cannot match pattern to given source: "%2$s".';

	/** @param-out int|string|list<int|string> $value */
	public static function walkRecursiveExtraction( string &$value, mixed $k, bool $toDigit = true ): void {
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
			|| throw new ScraperError( sprintf( self::INVALID_PATTERN_FOR_NUMERIC_VALUE, self::PATTERN_DEFINITION, $source ) );

		return $matched['value'];
	}

	public static function getRegexPattern(): string {
		$definedPattern = self::PATTERN_DEFINITION;
		$possiblePrefix = '?:[\[]? ?';

		return "/{$definedPattern}({$possiblePrefix})?(?<value>(?&bracketRange)|(?&dashRange)|(?&digits))/";
	}

	public static function maybeToDigit( string $value, bool $convert = true ): int|string {
		$value = trim( $value );

		return $convert && ctype_digit( $value ) ? abs( intval( $value ) ) : $value;
	}

	public function __construct( private readonly bool $numericToInteger = true ) {}

	public function transform( string|array|DOMElement $element, object $scope ): array {
		is_string( $element ) || throw new ScraperError(
			sprintf( 'Expected string value for digit transformation. "%s" type given.', get_debug_type( $element ) )
		);

		$extracted = $this->extractNumericValues( $element );

		array_walk( $extracted, self::walkRecursiveExtraction( ... ), $this->numericToInteger );

		return $extracted;
	}

	/**
	 * @param list<string> $range
	 * @return list<int|string>
	 */
	private static function rangeToDigits( array $range, bool $toDigit ): array {
		array_walk( $range, self::walkRecursiveExtraction( ... ), $toDigit );

		return $range;
	}
}
