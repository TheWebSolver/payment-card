<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Enums\Table;
use TheWebSolver\Codegarage\PaymentCard\Enums\Card;
use TheWebSolver\Codegarage\Scraper\Interfaces\Indexable;
use TheWebSolver\Codegarage\PaymentCard\Proxy\WikiTransformerProxy;

class WikiTransformerProxyTest extends TestCase {
	#[Test]
	#[DataProvider( 'provideElementContent' )]
	public function itTransformsElementByCardProperty( string $content, int|Card|null $propertyOrCount, mixed $expected ): void {
		$proxy = new WikiTransformerProxy();
		$scope = $this->createMock( Indexable::class );
		( $dom = new DOMDocument() )->loadHTML( $content );

		if ( $propertyOrCount instanceof Card ) {
			$scope->expects( $this->once() )
				->method( 'getCurrentItemIndex' )
				->willReturn( $propertyOrCount->value );
		} else {
			$scope->expects( $this->once() )
				->method( 'getCurrentIterationCount' )
				->with( Table::Column )
				->willReturn( $propertyOrCount );
		}

		$this->assertSame( $expected, $proxy->transform( $dom->getElementsByTagName( 'td' )->item( 0 ), $scope ) );
	}

	public static function provideElementContent(): array {
		$name      = '<td rowspan="2"><a href="/wiki/Discover_Card" title="Discover Card">Discover Card</a> <span>Suffix</span></td>';
		$range     = '<td>6011, 644-649, 65, 16-19<sup id="cite_ref-Discover_2017_Compliance_11-3" class="reference"><a href="#cite_note-Discover_2017_Compliance-11"><span class="cite-bracket">[</span>10<span class="cite-bracket">]</span></a></sup>, 622126-622925 (China UnionPay co-branded)</td>';
		$status    = '<td style="color:green">Yes (since 2017)<sup id="cite_ref-18" class="reference"><a href="#cite_note-18"><span class="cite-bracket">[</span>17<span class="cite-bracket">]</span></a></sup>\n</td>';
		$length    = '<td>16-19<sup id="cite_ref-Discover_2017_Compliance_11-4" class="reference"><a href="#cite_note-Discover_2017_Compliance-11"><span class="cite-bracket">[</span>10<span class="cite-bracket">]</span></a></sup>, <i>range</i> [10,12,14], <b>single</b> 8</td>';
		$validator = '<td rowspan="4"><a href="/wiki/Luhn_algorithm" title="Luhn algorithm">Luhn algorithm <span>Validator</span></a></td>';

		return [
			[ $name, 1, 'Discover Card' ],
			[ $name, Card::Name, 'Discover Card' ],
			[ $name, null, 'Discover Card Suffix' ],

			[ $range, 2, [ 6011, [ 644, 649 ], 65, [ 16, 19 ], [ 622126, 622925 ] ] ],
			[ $range, Card::IINRange, [ 6011, [ 644, 649 ], 65, [ 16, 19 ], [ 622126, 622925 ] ] ],
			// Test fails if HTML contains Entity/accented chars [–].
			[ $range, null, '6011, 644-649, 65, 16-19[10], 622126-622925 (China UnionPay co-branded)' ],

			[ $status, 3, 'Yes' ],
			[ $status, Card::Status, 'Yes' ],
			[ $status, null, 'Yes (since 2017)[17]\n' ],

			[ $length, 4, [ [ 16, 19 ], [ 10, 12, 14 ], 8 ] ],
			[ $length, Card::Length, [ [ 16, 19 ], [ 10, 12, 14 ], 8 ] ],
			[ $length, null, '16-19[10], range [10,12,14], single 8' ],

			[ $validator, 5, 'Luhn algorithm Validator' ],
			[ $validator, Card::Validator, 'Luhn algorithm Validator' ],
			[ $validator, null, 'Luhn algorithm Validator' ],
		];
	}
}
