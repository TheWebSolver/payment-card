<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Enums\Table;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Marshaller\MarshallItem;
use TheWebSolver\Codegarage\PaymentCard\Enums\PaymentCardProperty as Card;
use TheWebSolver\Codegarage\PaymentCard\Transformer\PaymentCardNumericPropertyTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\WikiPaymentCardNamePropertyTransformer;
use TheWebSolver\Codegarage\PaymentCard\Decorator\WikiPaymentCardNumericPropertyTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\WikiPaymentCardStatusPropertyTransformer;

/** @template-implements Transformer<Indexable,string|list<int|list<int>>> */
class WikiPaymentCardTransformerProxy implements Transformer {
	/** @param Transformer<contravariant Indexable,string> $base */
	public function __construct( private readonly Transformer $base = new MarshallItem() ) {}

	public function transform( string|array|DOMElement $element, object $scope ): string|array {
		$property = $scope->getCurrentItemIndex() ?? $scope->getCurrentIterationCount( Table::Column );

		return ( match ( $property ) {
			4, Card::Length->value,
			2, Card::IINRange->value => new WikiPaymentCardNumericPropertyTransformer( new PaymentCardNumericPropertyTransformer() ),
			3, Card::Status->value   => new WikiPaymentCardStatusPropertyTransformer(),
			1, Card::Name->value     => new WikiPaymentCardNamePropertyTransformer(),
			default                  => $this->base,
		} )->transform( $element, $scope );
	}
}
