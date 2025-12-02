<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\PaymentCard\Attributes\Card;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\CodeTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;

/**
 * @template-implements Transformer<
 *  BraintreeCardTracer,
 *  string|list<int|string|list<int|string>>|array{name:string,size:int|string}
 * >
 */
class BraintreeTransformerProxy implements Transformer {
	final public const INVALID_PATTERN_MATCH_ELEMENT = 'Expected array with string value for card detail extraction. "%1$s" type given. Array must be list of matched pattern and its group from regex: "%2$s".';
	/** @placeholder: `%S` String to extract card code. */
	final public const INVALID_JS_OBJECT_FOR_CARD_CODE = 'Invalid JS Object for extracting Braintree GitHub Card Code details. "%s" given.';
	/** @placeholder: `%S` String to extract card code details. */
	final public const INVALID_JS_OBJECT_KEYS_FOR_CARD_CODE = 'Card Code must have "name" and "size" key/value pair. "%s" given.';

	public function __construct( private readonly bool $numericToInteger = true ) {}

	public function transform( string|array|DOMElement $element, object $scope ): string|int|array {
		( ! is_array( $element ) || ! is_string( $value = $element['value'] ?? null ) ) && BraintreeCardTracer::throw(
			self::INVALID_PATTERN_MATCH_ELEMENT,
			get_debug_type( $element ),
			BraintreeCardTracer::getRegexPattern()
		);

		$value = trim( $value, '"' );
		$card  = $this->getCurrentCard( $element['property'], $scope->getCurrentItemIndex() );

		return match ( $card ) {
			Card::Length,
			Card::Breakpoint,
			Card::IINRange => ( new NumericTransformer( $this->numericToInteger ) )->transform( $value, $scope ),
			Card::Code     => ( new CodeTransformer( $this->numericToInteger ) )->transform( $value, $scope ),
			default        => $value,
		};
	}

	private function getCurrentCard( mixed $cardProperty, ?string $currentIndex ): Card {
		return $currentIndex ? Card::from( $currentIndex ) : BraintreeCardTracer::CARD_PROPERTIES[ $cardProperty ];
	}
}
