<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\PaymentCard\Enums\PaymentCardProperty as Card;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreePaymentCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\PaymentCardCodePropertyTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\PaymentCardNumericPropertyTransformer;

/** @template-implements Transformer<Indexable,string|list<int|list<int>>|array{name:string,size:int}> */
final class BraintreePaymentCardTransformerProxy implements Transformer {
	public const MISSING_PROPERTY_KEY = 'Element to transform Braintree Card Type must have "property" key/value pair.';
	/** @placeholder `1:` Given source type, `2:` Regex pattern to match card details extraction. */
	public const INVALID_ELEMENT = 'Invalid element type provided to transform Braintree GitHub Card Type. "%1$s" type given. It must have named groups: "property" and "value" from pattern matched regex: "%2$s".';

	public function transform( string|array|DOMElement $element, object $scope ): string|array {
		( ! is_array( $element ) || ! is_string( $value = $element['value'] ?? null ) ) && throw new InvalidSource(
			sprintf( self::INVALID_ELEMENT, get_debug_type( $element ), BraintreePaymentCardTracer::getRegexPattern() )
		);

		$card = $this->getCurrentCardFrom( $element, $scope->getCurrentItemIndex() );

		return match ( $card ) {
			Card::Length,
			Card::Breakpoint,
			Card::IINRange => ( new PaymentCardNumericPropertyTransformer() )->transform( $value, $scope ),
			Card::Code     => ( new PaymentCardCodePropertyTransformer() )->transform( $value, $scope ),
			default        => trim( $value, '"' ),
		};
	}

	/**
	 * @param mixed[] $element
	 * @throws InvalidSource When $element does not have "property" key/value pair.
	 */
	private function getCurrentCardFrom( array $element, ?string $currentIndex ): Card {
		return $currentIndex
			? Card::from( $currentIndex )
			: BraintreePaymentCardTracer::CARD_PROPERTIES[ $element['property'] ?? null ]
				?? throw new InvalidSource( self::MISSING_PROPERTY_KEY );
	}
}
