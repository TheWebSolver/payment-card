<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\PaymentCard\Attributes\Card;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\CodeTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;

/** @template-implements Transformer<BraintreeCardTracer,string|list<int|list<int>>|array{name:string,size:int}> */
class BraintreeTransformerProxy implements Transformer {
	/** @placeholder `1:` Given source type, `2:` Regex pattern to match card details extraction. */
	final public const INVALID_PATTERN_MATCH_ELEMENT = 'Invalid element type provided to transform Braintree GitHub Card Type. "%1$s" type given. It must have named groups: "property" and "value" from pattern matched regex: "%2$s".';

	public function transform( string|array|DOMElement $element, object $scope ): string|array {
		( ! is_array( $element ) || ! is_string( $value = $element['value'] ?? null ) ) && throw ScraperError::trigger(
			self::INVALID_PATTERN_MATCH_ELEMENT,
			get_debug_type( $element ),
			BraintreeCardTracer::getRegexPattern()
		);

		$card = $this->getCurrentCardFrom( $element['property'], $scope->getCurrentItemIndex() );

		return match ( $card ) {
			Card::Length,
			Card::Breakpoint,
			Card::IINRange => ( new NumericTransformer() )->transform( $value, $scope ),
			Card::Code     => ( new CodeTransformer() )->transform( $value, $scope ),
			default        => trim( $value, '"' ),
		};
	}

	private function getCurrentCardFrom( mixed $property, ?string $currentIndex ): Card {
		return $currentIndex ? Card::from( $currentIndex ) : BraintreeCardTracer::CARD_PROPERTIES[ $property ];
	}
}
