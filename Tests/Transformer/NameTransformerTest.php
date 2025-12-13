<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMElement;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NameTransformer;

class NameTransformerTest extends TestCase {
	#[Test]
	#[DataProvider( 'provideValidData' )]
	public function itTransformsNameOnlyFromDOMElement( string $content, mixed $expected ): void {
		( $dom = new DOMDocument() )->loadHTML( $content );

		$this->assertSame( $expected, ( new NameTransformer() )->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $dom ) );
	}

	/** @return mixed[] */
	public static function provideValidData(): array {
		return [
			[
				'<td><a href="/wiki/Diners_Club_International" title="Diners Club International">Diners Club</a> United States &amp; Canada<sup id="cite_ref-DinerUS_13-0" class="reference"><a href="#cite_note-DinerUS-13"><span class="cite-bracket">[</span>12<span class="cite-bracket">]</span></a></sup></td>',
				'Diners Club United States & Canada',
			],
			[
				'<td>  <p>ignore paragraph</p>  <span> ignore span </span> Capture This <b>ignore bold text</b>  </td>',
				'Capture This',
			],
			[
				'<td>  <p>ignore paragraph</p>   <span> ignore span </span>   <b>ignore bold text</b>   </td>',
				'',
			],
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

		( new NameTransformer() )->transform( $element, $this );
	}

	/** @return mixed[] */
	public static function provideInvalidData(): array {
		return [
			[ [], InvalidSource::class, 'Given node is not a DOMElement. Given type: "array".' ],
			[ '', InvalidSource::class, 'Given node is not a DOMElement. Given type: "string".' ],
		];
	}
}
