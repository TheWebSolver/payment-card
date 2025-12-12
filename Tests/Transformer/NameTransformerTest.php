<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NameTransformer;

class NameTransformerTest extends TestCase {
	#[Test]
	public function itTransformsNameOnlyFromDOMElement(): void {
		$transformer = new NameTransformer();

		// One of the <td> content from Wiki's scraped content.
		( $dom = new DOMDocument() )->loadHTML(
			'<td><a href="/wiki/Diners_Club_International" title="Diners Club International">Diners Club</a> United States &amp; Canada<sup id="cite_ref-DinerUS_13-0" class="reference"><a href="#cite_note-DinerUS-13"><span class="cite-bracket">[</span>12<span class="cite-bracket">]</span></a></sup></td>'
		);

		$this->assertSame(
			'Diners Club United States & Canada',
			$transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom )
		);

		( $dom = new DOMDocument() )->loadHTML(
			'<td>  <p>ignore paragraph</p>  <span> ignore span </span> Capture This <b>ignore bold text</b>  </td>'
		);

		$this->assertSame(
			'Capture This',
			$transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom )
		);

		( $dom = new DOMDocument() )->loadHTML(
			'<td>  <p>ignore paragraph</p>   <span> ignore span </span>   <b>ignore bold text</b>   </td>'
		);

		$this->assertSame( '', $transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );

		$this->expectException( InvalidSource::class );
		$this->expectExceptionMessage( 'Given node is not a DOMElement. Given type: "array".' );

		$transformer->transform( [], $dom );
	}
}
