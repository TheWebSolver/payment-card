<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Tracer;

use DOMElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Enums\EventAt;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Attributes\CollectUsing;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;
use TheWebSolver\Codegarage\PaymentCard\Enums\PaymentCardProperty as Card;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreePaymentCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Proxy\PaymentCardPropertyValidatorProxy;
use TheWebSolver\Codegarage\PaymentCard\Proxy\BraintreePaymentCardTransformerProxy;

class BraintreePaymentCardTracerTest extends TestCase {
	#[Test]
	public function itVerifiesGetterDefaultValue(): void {
		$tracer = new BraintreePaymentCardTracer();

		$this->assertFalse( $tracer->hasTransformer() );
		$this->assertNull( $tracer->getCurrentItemIndex() );
		$this->assertNull( $tracer->getCurrentIterationCount() );
		$this->assertNull( $tracer->getIndicesSource() );
		$this->assertFalse( $tracer->getData()->valid() );
	}

	#[Test]
	public function itReturnsStringifiedBraintreeCardTypePropertyNamesOrInitialsSeparatedByProvidedSeparator(): void {
		$this->assertSame( 'niceType|type|patterns|gaps|lengths|code', BraintreePaymentCardTracer::getPropNames() );
		$this->assertSame( 'n|t|p|g|l|c', BraintreePaymentCardTracer::getPropNames( initial: true ) );
		$this->assertSame( 'niceType", "type", "patterns", "gaps", "lengths", "code', BraintreePaymentCardTracer::getPropNames( separator: '", "' ) );
		$this->assertSame( 'n - t - p - g - l - c', BraintreePaymentCardTracer::getPropNames( initial: true, separator: ' - ' ) );
	}

	#[Test]
	public function itReturnsRegexPatternToMatchBraintreeCardTypePropertiesAndTheirRespectiveValues(): void {
		$define = sprintf( BraintreePaymentCardTracer::PATTERN_DEFINITION, 'niceType|type|patterns|gaps|lengths|code', 'n|t|p|g|l|c' );

		$this->assertSame(
			"/{$define}(?<property>(?&propertyName))(?&separator)(?<value>(?&everythingBeforeNextProperty)|(?&codePropertyValue))/",
			BraintreePaymentCardTracer::getRegexPattern()
		);
	}

	#[Test]
	public function itReturnsCardCasesWhenValidBraintreeCardTypePropertyNameIsGiven(): void {
		$this->assertSame( Card::Alias, BraintreePaymentCardTracer::getCardEnumBy( 'type' ) );
		$this->assertSame( Card::Name, BraintreePaymentCardTracer::getCardEnumBy( 'niceType' ) );
		$this->assertSame( Card::IINRange, BraintreePaymentCardTracer::getCardEnumBy( 'patterns' ) );
		$this->assertSame( Card::Breakpoint, BraintreePaymentCardTracer::getCardEnumBy( 'gaps' ) );
		$this->assertSame( Card::Length, BraintreePaymentCardTracer::getCardEnumBy( 'lengths' ) );
		$this->assertSame( Card::Code, BraintreePaymentCardTracer::getCardEnumBy( 'code' ) );
	}

	#[Test]
	#[DataProvider( 'provideInvalidPropertyNames' )]
	public function itThrowsExceptionWhenInvalidBraintreeCardTypePropertyNameIsGiven( string $propertyName, bool $source = false ): void {
		$expectedMsg = sprintf(
			BraintreePaymentCardTracer::INVALID_CARD_PROPERTIES,
			'niceType", "type", "patterns", "gaps", "lengths", "code',
			$source ? '. Property extraction source is :- this is a, test source' : ". \"{$propertyName}\" is not a valid property"
		);

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage( $expectedMsg );

		BraintreePaymentCardTracer::getCardEnumBy( $propertyName, $source ? 'this is a, test source' : '' );
	}

	/** @return mixed[] */
	public static function provideInvalidPropertyNames(): array {
		return [
			[ 'invalidProperty' ],
			[ 'Type', true ],
			[ 'NAME' ],
			[ 'Lengths', true ],
			[ '' ],
		];
	}

	#[Test]
	public function itAddsTransformer(): void {
		$tracer = new BraintreePaymentCardTracer();
		$tracer->addTransformer( $this->createStub( Transformer::class ) );

		$this->assertTrue( $tracer->hasTransformer() );
	}

	#[Test]
	public function itThrowsExceptionWhenIndicesSourceProvidedOutsideOfEventListener(): void {
		$tracer = new BraintreePaymentCardTracer();

		$tracer->addEventListener( static fn( $e )=> $e->tracer->setIndicesSource( new CollectUsing( Card::class ) ), eventAt: EventAt::Start )
			->inferFrom( BraintreePaymentCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . '}', true );

		$this->assertInstanceOf( CollectUsing::class, $tracer->getIndicesSource() );

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage(
			sprintf( BraintreePaymentCardTracer::USE_EVENT_LISTENER, BraintreePaymentCardTracer::class . '::setIndicesSource', EventAt::class . '::' . EventAt::Start->name, 'set Card Type property names' )
		);

		( new BraintreePaymentCardTracer() )->setIndicesSource( new CollectUsing( Card::class ) );
	}

	#[Test]
	#[DataProvider( 'provideInvalidSourceTypes' )]
	public function itThrowsExceptionIfPatternMatchFails( string|DOMElement $source, string $errorMsg, bool $afterIteration = false ): void {
		$tracer = new BraintreePaymentCardTracer();

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage( $errorMsg );
		$tracer->inferFrom( $source, normalize: false );

		$afterIteration && $tracer->getData()->valid();
	}

	/** @return mixed[] */
	public static function provideInvalidSourceTypes(): array {
		$validContent = file_get_contents( CardFactory::RESOURCE_PATH . '/cards.ts' ) ?: '';

		return [
			[ new DOMElement( 'invalid' ), BraintreePaymentCardTracer::INVALID_SOURCE_TYPE ],
			[ 'invalid raw GitHub Content', BraintreePaymentCardTracer::INVALID_SOURCE_TYPE ],
			// Valid but non-normalized content.
			[ $validContent, 'Braintree GitHub Card Type', true ],
			[ BraintreePaymentCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . ' "test-card": "must-be-object enclosed by "{" and "}""', 'Braintree GitHub Card Type', true ],
			// Regex matches nothing.
			[
				BraintreePaymentCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . ' card: { type: "valid", invalidPropertyName: {} },',
				sprintf( 'Cannot match pattern to given subject: "%s"', 'type: "valid", invalidPropertyName: {}' ),
				true,
			],
		];
	}

	#[Test]
	public function itEnsuresCurrentIterationCountAndPropertyName(): void {
		$tracer     = new BraintreePaymentCardTracer();
		$cardObject = 'const cardTypes: CardCollection = {
  maestro: {
    niceType: "Maestro",
    type: "maestro",
    patterns: [
      493698,
      [500000, 504174],
      [504176, 506698],
      [506779, 508999],
      [56, 59],
      63,
      67,
      6,
    ],
    gaps: [4, 8, 12],
    lengths: [12, 13, 14-16, 17, 18, 19],
    code: {
      name: "CVC",
      size: 3,
    },
  } as BuiltInCreditCardType,
};';

		$tracer->addTransformer( new IterationAssertionTransformer() )->inferFrom( $cardObject, normalize: true );
		$tracer->getData()->current();
	}

	/**
	 * @param array{0:int,1:string,2:string} $expected
	 * @param mixed[]                        $actual
	 */
	public static function assertCurrentIterationProperty( BraintreePaymentCardTracer $scope, array $expected, array $actual ): string {
		[ $count, $name, $value ] = $expected;

		self::assertSame( $count, $scope->getCurrentIterationCount() );
		self::assertSame( $name, $actual['property'] );
		self::assertSame( $value, $actual['value'] );

		return $value;
	}

	#[Test]
	public function itValidatesTransformedValuesAccordingToCurrentCardProperty(): void {
		$tracer     = new BraintreePaymentCardTracer();
		$cardObject = 'const cardTypes: CardCollection = {
      mastercard: {
        niceType: "Mastercard",
        type: "mastercard",
        patterns: [[51, 55], [2221, 2229], [223, 229], [23, 26], [270, 271], 2720],
        gaps: [4, 8, 12],
        lengths: [16],
        code: {
          name: "CVC",
          size: 3,
        },
      } as BuiltInCreditCardType,
	  }';

		$tracer->addTransformer( new PaymentCardPropertyValidatorProxy( new BraintreePaymentCardTransformerProxy() ) ); // @phpstan-ignore-line
		$tracer->inferFrom( $cardObject, normalize: true );

		$iterator = $tracer->getData();

		$this->assertSame(
			[ [ 51, 55 ], [ 2221, 2229 ], [ 223, 229 ], [ 23, 26 ], [ 270, 271 ], 2720 ],
			$iterator->current()[ Card::IINRange->value ]
		);
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/** @template-implements Transformer<BraintreePaymentCardTracer,mixed> */
class IterationAssertionTransformer implements Transformer {
	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		assert( is_array( $element ) );

		// These assertions are made 6 times, for each Card property iteration.
		BraintreePaymentCardTracerTest::assertInstanceOf( Card::class, $card = Card::from( $scope->getCurrentItemIndex() ?? '' ) );
		BraintreePaymentCardTracerTest::assertArrayHasKey( 'property', $element );
		BraintreePaymentCardTracerTest::assertArrayHasKey( 'value', $element );

		return match ( $card ) {
			default          => null,
			Card::Name       => BraintreePaymentCardTracerTest::assertCurrentIterationProperty( $scope, [ 1,  'niceType', '"Maestro"' ], $element ),
			Card::Alias      => BraintreePaymentCardTracerTest::assertCurrentIterationProperty( $scope, [ 2, 'type', '"maestro"' ], $element ),
			Card::Breakpoint => BraintreePaymentCardTracerTest::assertCurrentIterationProperty( $scope, [ 4, 'gaps', '[4, 8, 12]' ], $element ),
			Card::Length     => BraintreePaymentCardTracerTest::assertCurrentIterationProperty( $scope, [ 5, 'lengths', '[12, 13, 14-16, 17, 18, 19]' ], $element ),
			Card::Code       => BraintreePaymentCardTracerTest::assertCurrentIterationProperty( $scope, [ 6, 'code', '{ name: "CVC", size: 3, }' ], $element ),
			Card::IINRange   => BraintreePaymentCardTracerTest::assertCurrentIterationProperty(
				$scope,
				[ 3, 'patterns', '[ 493698, [500000, 504174], [504176, 506698], [506779, 508999], [56, 59], 63, 67, 6, ]' ],
				$element
			),
		};
	}
}
