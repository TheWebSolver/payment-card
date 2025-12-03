<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,array{name:string,size:int|string}> */
class CodeTransformer implements Transformer {
	/** @placeholder `%s:` Card's Code property names. */
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<properties>[%s]+)(?<separator>[\:\" ]+?)(?<names>[A-Z]{3})(?<sizes>[\d]{1}))';

	final public const PROPERTIES = [ 'name', 'size' ];

	final public const INVALID_ELEMENT = 'Invalid element type provided to transform Card\'s Code.';
	/** @placeholder: `1:` Card's Code property names, `2:` The given string. */
	final public const INVALID_PROPERTIES = 'Card\'s Code must have values associated with properties "%1$s". "%2$s" given.';

	public static function getRegexPattern(): string {
		$define = sprintf( self::PATTERN_DEFINITION, implode( '|', self::PROPERTIES ) );

		return "/{$define}(?<name>(?&properties))(?&separator)(?<value>(?&names)|(?&sizes))/";
	}

	public function __construct( private readonly bool $numericToInteger = true ) {}

	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		$value = match ( true ) {
			$element instanceof DOMElement         => $element->textContent,
			is_string( $element )                  => $element,
			is_string( $element['value'] ?? null ) => $element['value'],
			default                                => throw ScraperError::trigger( self::INVALID_ELEMENT )
		};

		return $this->extractCardCodeDetails( $value );
	}

	/**
	 * @return array{name:string,size:int|string}
	 * @throws ScraperError When Card's Code source is invalid.
	 */
	private function extractCardCodeDetails( string $source ): array {
		$details = preg_match_all( $this->getRegexPattern(), $source, $matches, PREG_SET_ORDER )
			? array_reduce( $matches, $this->reduceToProperties( ... ), initial: [] )
			: null;

		return $details ?: ScraperError::patternMismatch( "Card's Code", self::getRegexPattern(), $source );
	}

	/**
	 * @param array{name?:string,size?:int|string} $properties
	 * @param array<string>                        $property
	 * @return array{name:string,size:int|string}
	 * @throws ScraperError When Card's Code properties are invalid.
	 */
	private function reduceToProperties( array $properties, array $property ): array {
		$propertyName = $property['name'] ?? null;

		match ( $propertyName ) {
			'name'  => $properties['name'] = $property['value'],
			'size'  => $properties['size'] = NumericTransformer::maybeToDigit( $property['value'], $this->numericToInteger ),
			default => throw ScraperError::trigger( self::INVALID_PROPERTIES, implode( '", "', self::PROPERTIES ), $property[0] ),
		};

		assert( isset( $properties['name'], $properties['size'] ) );

		return $properties;
	}
}
