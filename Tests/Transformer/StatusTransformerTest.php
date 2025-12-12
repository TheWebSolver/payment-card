<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\StatusTransformer;

class StatusTransformerTest extends TestCase {
	#[Test]
	public function itTransformsStatusOnlyFromDOMElement(): void {
		$transformer = new StatusTransformer();

		// One of the <td> content from Wiki's scraped content.
		( $dom = new DOMDocument() )->loadHTML(
			'<td style="color:green">Yes (since 2017)<sup id="cite_ref-18" class="reference"><a href="#cite_note-18"><span class="cite-bracket">[</span>17<span class="cite-bracket">]</span></a></sup></td>'
		);

		$this->assertSame( 'Yes', $transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );

		( $dom = new DOMDocument() )->loadHTML(
			'<td> <!-- If content starts with "No", then "No" --> No  <span>Yes</span> </td>'
		);

		$this->assertSame( 'No', $transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );

		( $dom = new DOMDocument() )->loadHTML(
			'<td> If content does not start with "No", then "Yes" </td>'
		);

		$this->assertSame( 'Yes', $transformer->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );

		$this->expectException( InvalidSource::class );
		$this->expectExceptionMessage( 'Given node is not a DOMElement. Given type: "array".' );

		$transformer->transform( [], $dom );
	}
}
