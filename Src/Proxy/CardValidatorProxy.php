<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Interfaces\Validatable;

/** @template-implements Transformer<Validatable<string|list<int|list<int>>|array{name:string,size:int}>,string|list<int|list<int>>|array{name:string,size:int}> */
class CardValidatorProxy implements Transformer {
	/** @param Transformer<object,string|list<int|list<int>>|array{name:string,size:int}> $base */
	public function __construct( private readonly Transformer $base ) {}

	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		$scope->validate( $value = $this->base->transform( $element, $scope ) );

		return $value;
	}
}
