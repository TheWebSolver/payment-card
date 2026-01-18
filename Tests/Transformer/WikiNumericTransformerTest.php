<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\PaymentCard\Decorator\WikiPaymentCardNumericPropertyTransformer;

class WikiPaymentCardNumericPropertyTransformerTest extends TestCase {
	#[Test]
	public function itTransformsNumericValuesToDigitsFromDOMElementChildren(): void {
		$transformer = $this->createMock( Transformer::class );
		( $dom = new DOMDocument() )
			->loadHTML( '<td>12 - 34, <!-- ignore 5 in comment --> 6, <pre>ignore nested 7 number</pre>[8, 9] <b>whatever</b>  10</td>' );

		$transformer->expects( $this->once() )
			->method( 'transform' )
			->with( '12 - 34, 6, [8, 9] 10' )
			->willReturn( [ [ 12, 34 ], 6, [ 8, 9 ], 10 ] );

		( new WikiPaymentCardNumericPropertyTransformer( $transformer ) )->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom );
	}
}
