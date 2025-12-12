<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,array{name:string,size:int}> */
class CodeTransformer implements Transformer {
	/** @placeholder `%s:` Card's Code property names. */
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<properties>(%s))(?<separator>[\:\" ]+)(?<names>[A-Z]{3})(?<sizes>[\d]{1}))';

	final public const PROPERTIES = [ 'name', 'size' ];

	final public const INVALID_ELEMENT = 'Invalid element type provided to transform Card\'s Code.';
	/** @placeholder: `%s:` Missing property name. */
	final public const MISSING_PROPERTY = 'Missing required "%s" property in Card\'s Code transformation.';

	public static function getRegexPattern(): string {
		$define = sprintf( self::PATTERN_DEFINITION, implode( '|', self::PROPERTIES ) );

		return "/{$define}(?<name>(?&properties))(?&separator)(?<value>(?&names)|(?&sizes))/";
	}

	/**
	 * @return array{name:string,size:int}
	 * @throws ScraperError When Card's Code source regex match fails or property is missing.
	 */
	public static function transformCode( string $source ): array {
		$details = preg_match_all( self::getRegexPattern(), $source, $matches, PREG_SET_ORDER )
			? array_reduce( $matches, self::reduceToProperties( ... ), initial: [] )
			: null;

		return match ( true ) {
			is_null( $details )         => throw ScraperError::patternMismatch( "Card's Code", self::getRegexPattern(), $source ),
			! isset( $details['name'] ) => throw ScraperError::trigger( self::MISSING_PROPERTY, 'name' ),
			! isset( $details['size'] ) => throw ScraperError::trigger( self::MISSING_PROPERTY, 'size' ),
			default                     => $details
		};
	}

	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		$source = match ( true ) {
			$element instanceof DOMElement         => $element->textContent,
			is_string( $element )                  => $element,
			is_string( $element['value'] ?? null ) => $element['value'],
			default                                => throw new InvalidSource( self::INVALID_ELEMENT )
		};

		return $this->transformCode( $source );
	}

	/**
	 * @param array{name?:string,size?:int} $properties
	 * @param string[]                      $property
	 * @return array{name?:string,size?:int}
	 * @throws ScraperError When Card's Code properties are invalid.
	 */
	private static function reduceToProperties( array $properties, array $property ): array {
		$propertyName = $property['name'];

		if ( 'name' === $propertyName ) {
			$properties['name'] = $property['value'];
		} elseif ( 'size' === $propertyName ) {
			$properties['size'] = NumericTransformer::maybeToDigit( $property['value'] );
		}

		return $properties;
	}
}
