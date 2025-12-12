<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;
use TheWebSolver\Codegarage\PaymentCard\Decorator\WikiNumericTransformer;

class WikiNumericTransformerTest extends TestCase {
	#[Test]
	public function itTransformsNumericValuesToDigitsFromDOMElementChildren(): void {
		$transformer = new WikiNumericTransformer( new NumericTransformer() );
		$dom         = new DOMDocument();

		$dom->loadHTML( '<div>12 - 34, <!-- ignore 5 in comment --> 6, <pre>ignore nested 7 number</pre>[8, 9] <b>whatever</b>  10</div>' );

		$this->assertSame(
			[ [ 12, 34 ], 6, [ 8, 9 ], 10 ],
			$transformer->transform( $dom->getElementsByTagName( 'body' )->item( 0 )->firstChild, $dom )
		);
	}
}
