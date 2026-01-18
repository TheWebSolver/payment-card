<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use stdClass;
use DOMElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Error\InvalidSource;
use TheWebSolver\Codegarage\PaymentCard\Transformer\PaymentCardNumericPropertyTransformer;

class PaymentCardNumericPropertyTransformerTest extends TestCase {
	#[Test]
	public function itVerifiesRegexPattern(): void {
		$define   = PaymentCardNumericPropertyTransformer::PATTERN_DEFINITION;
		$expected = "/{$define}(?&maybeBracketOpen)(?&valueSeparator)?(?<value>(?&bracketRange)|(?&dashRange)|(?&digits))/";

		$this->assertSame( $expected, PaymentCardNumericPropertyTransformer::getRegexPattern() );
	}

	#[Test]
	#[DataProvider( 'provideNumericStringToDigitValues' )]
	public function itConvertsStringToDigit( string $value, int $expected ): void {
		$this->assertSame( $expected, PaymentCardNumericPropertyTransformer::toDigit( $value ) );
	}

	public static function provideNumericStringToDigitValues(): array {
		return [
			[ '12345', 12345 ],
			[ '   67890   ', 67890 ],
			[ '0', 0 ],
			[ '-42', 42 ],
			[ '-999.99', 999 ],
			[ 'some123text', 0 ],
			[ '  123abc456', 123 ],
			[ '  12 3abc 456', 12 ],
		];
	}

	/**
	 * @param string    $source
	 * @param ?string[] $expected
	 */
	#[Test]
	#[DataProvider( 'provideDifferentPatternMatchValues' )]
	public function itExtractsNumericValuesFromString( string $source, ?array $expected ): void {
		is_null( $expected ) && $this->expectExceptionMessage( sprintf( 'Cannot match pattern to given subject: "%s"', $source ) );

		$this->assertSame( PaymentCardNumericPropertyTransformer::extractNumericValues( $source ), $expected );
	}

	public static function provideDifferentPatternMatchValues(): array {
		return [
			[
				'77-88, 22,13 [9,6,3]5',
				[ '77-88', '22', '13', '[9,6,3]', '5' ],
			],
			[
				'   77    -    88  , 22,   [ 9  , 6,   3 ]   ',
				[ '77    -    88', '22', '[ 9  , 6,   3 ]' ],
			],
			[
				'prefix 12345 suffix, this 11 - 22 that, [ bracket, 1, can, 2, have, 3, only digits ], invalid8-dash9',
				[ '12345', '11 - 22', '1', '2', '3', '8', '9' ],
			],
			[ 'No numbers here!', null ],
			[ 'nothing, [also, no, numbers], not-number', null ],
		];
	}

	/**
	 * @param string[] $values
	 * @param ?mixed[] $expected
	*/
	#[Test]
	#[DataProvider( 'provideDifferentMappableValues' )]
	public function itMapsValuesByEitherSingleNumericOrNumericRange( array $values, ?array $expected ): void {
		$this->assertSame( $expected, array_map( PaymentCardNumericPropertyTransformer::mapExtractedValue( ... ), $values ) );
	}

	/** @return mixed[] */
	public static function provideDifferentMappableValues(): array {
		return [
			[ [ '[100,200,300]' ], [ [ 100, 200, 300 ] ] ],
			[ [ '[ 100,    200,      300 ]' ], [ [ 100, 200, 300 ] ] ],
			[ [ '400-500' ], [ [ 400, 500 ] ] ],
			[ [ '    400   -   500     ' ], [ [ 400, 500 ] ] ],
			[ [ '600', '[700, 800]', '5-6', '1–3', '9' ], [ 600, [ 700, 800 ], [ 5, 6 ], [ 1, 3 ], 9 ] ],
		];
	}

	/**
	 * @param string|mixed[]|DOMElement $element
	 * @param string|mixed[]            $expected
	 */
	#[Test]
	#[DataProvider( 'provideTransformationElement' )]
	public function itTransformsNumericValuesToDigits( string|array|DOMElement $element, string|array $expected ): void {
		if ( is_string( $expected ) ) {
			$this->expectException( InvalidSource::class );
			$this->expectExceptionMessage( $expected );
		}

		$this->assertSame( $expected, ( new PaymentCardNumericPropertyTransformer() )->transform( $element, new stdClass() ) );
	}

	/** @return mixed[] */
	public static function provideTransformationElement(): array {
		return [
			[
				'[775557777-8688889999, 45, 99, 5-6, [ 12,    13, 14 ], 622126–622925 (China UnionPay co-branded), 6011, 644-649, 65, 60400100–60420099, 353, 356 (RuPay-JCB co-branded)]',
				[ [ 775557777, 8688889999 ], 45, 99, [ 5, 6 ], [ 12, 13, 14 ], [ 622126, 622925 ], 6011, [ 644, 649 ], 65, [ 60400100, 60420099 ], 353, 356 ],
			],
			[ [], sprintf( PaymentCardNumericPropertyTransformer::INVALID_ELEMENT, 'array' ) ],
			[ new DOMElement( 'div' ), sprintf( PaymentCardNumericPropertyTransformer::INVALID_ELEMENT, 'DOMElement' ) ],
		];
	}
}
