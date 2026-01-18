<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Transformer;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\AssertDOMElement;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,string> */
class WikiPaymentCardStatusPropertyTransformer implements Transformer {
	public function transform( string|array|DOMElement $element, object $scope ): string {
		AssertDOMElement::instance( $element );

		return str_starts_with( trim( $element->textContent ), 'No' ) ? 'No' : 'Yes';
	}
}
