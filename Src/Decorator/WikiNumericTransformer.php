<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Decorator;

use DOMText;
use DOMElement;
use TheWebSolver\Codegarage\Scraper\AssertDOMElement;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;

/** @template-implements Transformer<object,list<int|string|list<int|string>>> */
class WikiNumericTransformer implements Transformer {
	/** @param Transformer<object,list<int|string|list<int|string>>> $numericTransformer */
	public function __construct( private Transformer $numericTransformer ) {}

	public function transform( string|array|DOMElement $element, object $scope ): array {
		AssertDOMElement::instance( $element );

		$value = '';

		foreach ( $element->childNodes as $node ) {
			$node instanceof DOMText && ( $value .= trim( $node->textContent ) );
		}

		return $value ? $this->numericTransformer->transform( $value, $scope ) : [];
	}
}
