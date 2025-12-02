<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Proxy;

use DOMElement;
use TheWebSolver\Codegarage\Scraper\Enums\Table;
use TheWebSolver\Codegarage\PaymentCard\Attributes\Card;
use TheWebSolver\Codegarage\Scraper\Interfaces\TableTracer;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Marshaller\MarshallItem;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NameTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\StatusTransformer;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;
use TheWebSolver\Codegarage\PaymentCard\Decorator\WikiNumericTransformer;

/** @template-implements Transformer<TableTracer<string>,string|list<int|string|list<int|string>>> */
class WikiTransformerProxy implements Transformer {
	/** @param Transformer<contravariant TableTracer<string>,string> $base */
	public function __construct(
		private readonly Transformer $base = new MarshallItem(),
		private readonly bool $numericToInteger = true
	) {}

	public function transform( string|array|DOMElement $element, object $scope ): string|array {
		$current = $scope->getCurrentItemIndex() ?? $scope->getCurrentIterationCount( Table::Column );

		return ( match ( $current ) {
			default                    => $this->base,
			1, Card::Name->value       => new NameTransformer(),
			3, Card::Status->value     => new StatusTransformer(),
			2, 4, Card::Length->value,
			Card::IINRanges->value     => new WikiNumericTransformer( new NumericTransformer( $this->numericToInteger ) ),
		} )->transform( $element, $scope );
	}
}
