<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Proxy\BraintreeTransformerProxy;

/** @template-implements Transformer<object,array{name:string,size:int|string}> */
class CodeTransformer implements Transformer {
	final public const PATTERN_DEFINITION = '(?(DEFINE)(?<properties>[name|size]+)(?<separator>[\:\" ]+?)(?<names>[A-Z]+)(?<sizes>[\d{1}]))';

	final public const CARD_CODE_PROPERTIES = [ 'name', 'size' ];
	/** @placeholder: `%s` String to extract card code. */
	final public const INVALID_JS_OBJECT_FOR_CARD_CODE = 'Invalid JS Object for extracting Braintree GitHub Card Code details. "%s" given.';
	/** @placeholder: `%s` String to extract card code details. */
	final public const INVALID_JS_OBJECT_KEYS_FOR_CARD_CODE = 'Card Code must have "name" and "size" key/value pair. "%s" given.';

	public static function getRegexPattern(): string {
		$define = self::PATTERN_DEFINITION;

		return "/{$define}(?<property>(?&properties))(?&separator)(?<value>(?&names)|(?&sizes))/";
	}

	public function __construct( private readonly bool $numericToInteger = true ) {}

	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		$value = match ( true ) {
			$element instanceof DOMElement         => $element->textContent,
			is_string( $element )                  => $element,
			is_string( $element['value'] ?? null ) => $element['value'],
			default                                => BraintreeCardTracer::throw(
				BraintreeTransformerProxy::INVALID_PATTERN_MATCH_ELEMENT,
				get_debug_type( $element ),
				BraintreeCardTracer::getRegexPattern()
			)
		};

		return $this->extractCardCodeDetails( $value );
	}

	/** @return array{name:string,size:int|string} */
	private function extractCardCodeDetails( string $raw ): array {
		$details = preg_match_all( $this->getRegexPattern(), $raw, $matches, PREG_SET_ORDER )
			? array_reduce( $matches, $this->toCardCodeProperties( ... ), initial: [] )
			: null;

		return $details ?: BraintreeCardTracer::throw( self::INVALID_JS_OBJECT_FOR_CARD_CODE, $raw );
	}

	/**
	 * @param array{name?:string,size?:int|string} $carry
	 * @param array<string>                        $matched
	 * @return array{name:string,size:int|string}
	 */
	private function toCardCodeProperties( array $carry, array $matched ): array {
		$key = $matched['property'] ?? null;

		match ( $key ) {
			'name'  => $carry[ $key ] = $matched['value'],
			'size'  => $carry[ $key ] = NumericTransformer::maybeToDigit( $matched['value'], $this->numericToInteger ),
			default => BraintreeCardTracer::throw( self::INVALID_JS_OBJECT_KEYS_FOR_CARD_CODE, $matched[0] ),
		};

		assert( isset( $carry['name'], $carry['size'] ) );

		return $carry;
	}
}
