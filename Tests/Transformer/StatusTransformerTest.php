<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMElement;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\StatusTransformer;

class StatusTransformerTest extends TestCase {
	#[Test]
	#[DataProvider( 'provideValidData' )]
	public function itTransformsStatusOnlyFromDOMElement( string $content, mixed $expected ): void {
		( $dom = new DOMDocument() )->loadHTML( $content );

		$this->assertSame( $expected, ( new StatusTransformer() )->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );
	}

	/** @return mixed[] */
	public static function provideValidData(): array {
		return [
			// One of the <td> content from Wiki's scraped content.
			[
				'<td style="color:green">Yes (since 2017)<sup id="cite_ref-18" class="reference"><a href="#cite_note-18"><span class="cite-bracket">[</span>17<span class="cite-bracket">]</span></a></sup></td>',
				'Yes',
			],
			[ '<td> <!-- If content starts with "No", then "No" --> No  <span>Yes</span> </td>', 'No' ],
			[ '<td> If content does not start with "No", then "Yes" </td>', 'Yes' ],
		];
	}

	/**
	 * @param string|mixed[]|DOMElement $element
	 * @param class-string              $exceptionClass
	 */
	#[Test]
	#[DataProvider( 'provideInvalidData' )]
	public function itThrowsExceptionWhenInvalidElementProvided( string|array|DOMElement $element, string $exceptionClass, string $msg ): void {
		$this->expectException( $exceptionClass );
		$this->expectExceptionMessage( $msg );

		( new StatusTransformer() )->transform( $element, $this );
	}

	/** @return mixed[] */
	public static function provideInvalidData(): array {
		return [
			[ [], InvalidSource::class, 'Given node is not a DOMElement. Given type: "array".' ],
			[ '', InvalidSource::class, 'Given node is not a DOMElement. Given type: "string".' ],
		];
	}
}
