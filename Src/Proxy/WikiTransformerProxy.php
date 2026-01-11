<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Enums\Table;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Marshaller\MarshallItem;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NameTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\StatusTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;
use TheWebSolver\Codegarage\PaymentCard\Decorator\WikiNumericTransformer;
use TheWebSolver\Codegarage\PaymentCard\Enums\PaymentCardProperty as Card;

/** @template-implements Transformer<Indexable,string|list<int|list<int>>> */
class WikiTransformerProxy implements Transformer {
	/** @param Transformer<contravariant Indexable,string> $base */
	public function __construct( private readonly Transformer $base = new MarshallItem() ) {}

	public function transform( string|array|DOMElement $element, object $scope ): string|array {
		$property = $scope->getCurrentItemIndex() ?? $scope->getCurrentIterationCount( Table::Column );

		return ( match ( $property ) {
			4, Card::Length->value,
			2, Card::IINRange->value => new WikiNumericTransformer( new NumericTransformer() ),
			3, Card::Status->value   => new StatusTransformer(),
			1, Card::Name->value     => new NameTransformer(),
			default                  => $this->base,
		} )->transform( $element, $scope );
	}
}
