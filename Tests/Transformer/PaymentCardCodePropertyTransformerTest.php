<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use stdClass;
use DOMElement;
use DOMDocument;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\PaymentCardCodePropertyTransformer;

class PaymentCardCodePropertyTransformerTest extends TestCase {
	#[Test]
	public function itVerifiesRegexPattern(): void {
		$define   = sprintf( PaymentCardCodePropertyTransformer::PATTERN_DEFINITION, 'name|size' );
		$expected = "/{$define}(?<name>(?&properties))(?&separator)(?<value>(?&names)|(?&sizes))/";

		$this->assertSame( $expected, PaymentCardCodePropertyTransformer::getRegexPattern() );
	}

	/**
	 * @param string|mixed[]|DOMElement $element
	 * @param string|mixed[]            $expected
	 */
	#[Test]
	#[DataProvider( 'provideCodeElements' )]
	public function itTransformsGivenElement( string|array|DOMElement $element, string|array $expected, string $throwable = '' ): void {
		if ( is_string( $expected ) ) {
			$this->expectException( $throwable );
			$this->expectExceptionMessage( $expected );
		}

		$this->assertSame( $expected, ( new PaymentCardCodePropertyTransformer() )->transform( $element, new stdClass() ) );
	}

	public static function provideCodeElements(): array {
		$dom = new DOMDocument();
		$dom->loadHTML( '<td>name:"CVC" size:3</td>' );

		return [
			[
				'name:"CVC" size:3',
				[
					'name' => 'CVC',
					'size' => 3,
				],
			],
			[
				'{
           name: "CVC",
           size: 3
        }',
				[
					'name' => 'CVC',
					'size' => 3,
				],
			],
			[
				[ 'value' => 'name:"CVV" size:4' ],
				[
					'name' => 'CVV',
					'size' => 4,
				],
			],
			[
				[
					'value' => '{
            name: "CVC",
            size: 3
          }',
				],
				[
					'name' => 'CVC',
					'size' => 3,
				],
			],
			[
				$dom->getElementsByTagName( 'td' )->item( 0 ),
				[
					'name' => 'CVC',
					'size' => 3,
				],
			],
			[
				[ 'key-must-be-named-"value"' => 'name:"CVV" size:4' ],
				PaymentCardCodePropertyTransformer::INVALID_ELEMENT,
				InvalidSource::class,
			],
			[
				'{names:"CVV", sizes:3}',
				'Cannot match pattern to given subject: "{names:"CVV", sizes:3}"',
				ScraperError::class,
			],
			[
				'{names:"CVV", size:3}',
				sprintf( PaymentCardCodePropertyTransformer::MISSING_PROPERTY, 'name' ),
				ScraperError::class,
			],
			[
				'{name:"CVV", sizes:3}',
				sprintf( PaymentCardCodePropertyTransformer::MISSING_PROPERTY, 'size' ),
				ScraperError::class,
			],
		];
	}
}
