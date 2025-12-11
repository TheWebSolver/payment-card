<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Tracer;

use DOMElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\Scraper\Enums\EventAt;
use TheWebSolver\Codegarage\PaymentCard\Enums\Card;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;
use TheWebSolver\Codegarage\Scraper\Error\ScraperError;
use TheWebSolver\Codegarage\Scraper\Interfaces\Transformer;
use TheWebSolver\Codegarage\Scraper\Attributes\CollectUsing;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;

class BraintreeTracerTest extends TestCase {
	#[Test]
	public function getterDefaultValue(): void {
		$tracer = new BraintreeCardTracer();

		$this->assertFalse( $tracer->hasTransformer() );
		$this->assertNull( $tracer->getCurrentItemIndex() );
		$this->assertNull( $tracer->getCurrentIterationCount() );
		$this->assertNull( $tracer->getIndicesSource() );
		$this->assertFalse( $tracer->getData()->valid() );
	}

	#[Test]
	public function returnsStringifiedBraintreeCardTypePropertyNamesOrInitialsSeparatedByProvidedSeparator(): void {
		$this->assertSame( 'niceType|type|patterns|gaps|lengths|code', BraintreeCardTracer::getPropNames() );
		$this->assertSame( 'n|t|p|g|l|c', BraintreeCardTracer::getPropNames( initial: true ) );
		$this->assertSame( 'niceType", type", patterns", gaps", lengths", code', BraintreeCardTracer::getPropNames( separator: '", ' ) );
		$this->assertSame( 'n - t - p - g - l - c', BraintreeCardTracer::getPropNames( initial: true, separator: ' - ' ) );
	}

	#[Test]
	public function returnsRegexPatternToMatchBraintreeCardTypePropertiesAndTheirRespectiveValues(): void {
		$define = sprintf( BraintreeCardTracer::PATTERN_DEFINITION, 'niceType|type|patterns|gaps|lengths|code', 'n|t|p|g|l|c' );

		$this->assertSame(
			"/{$define}(?<property>(?&propertyName))(?&separator)(?<value>(?&everythingBeforeNextProperty)|(?&codePropertyValue))/",
			BraintreeCardTracer::getRegexPattern()
		);
	}

	#[Test]
	public function returnsCardCasesWhenValidBraintreeCardTypePropertyNameIsGiven(): void {
		$this->assertSame( Card::Alias, BraintreeCardTracer::getCardEnumBy( 'type' ) );
		$this->assertSame( Card::Name, BraintreeCardTracer::getCardEnumBy( 'niceType' ) );
		$this->assertSame( Card::IINRange, BraintreeCardTracer::getCardEnumBy( 'patterns' ) );
		$this->assertSame( Card::Breakpoint, BraintreeCardTracer::getCardEnumBy( 'gaps' ) );
		$this->assertSame( Card::Length, BraintreeCardTracer::getCardEnumBy( 'lengths' ) );
		$this->assertSame( Card::Code, BraintreeCardTracer::getCardEnumBy( 'code' ) );
	}

	#[Test]
	#[DataProvider( 'provideInvalidPropertyNames' )]
	public function throwsExceptionWhenInvalidBraintreeCardTypePropertyNameIsGiven( string $propertyName, bool $source = false ): void {
		$sourceMsg   = $source ? 'this is a, test source' : '';
		$invalidProp = $source ? '' : ". \"{$propertyName}\" is not a valid property";
		$expectedMsg = sprintf(
			BraintreeCardTracer::INVALID_CARD_PROPERTIES,
			'niceType", "type", "patterns", "gaps", "lengths", "code',
			$invalidProp,
			$sourceMsg ? '. Property extraction source is :- this is a, test source' : ''
		);

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage( $expectedMsg );

		BraintreeCardTracer::getCardEnumBy( $propertyName, $sourceMsg );
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
	public function addsTransformer(): void {
		$tracer = new BraintreeCardTracer();
		$tracer->addTransformer( $this->createStub( Transformer::class ) );

		$this->assertTrue( $tracer->hasTransformer() );
	}

	#[Test]
	public function throwsExceptionWhenIndicesSourceProvidedOutsideOfEventListener(): void {
		$tracer = new BraintreeCardTracer();

		$tracer->addEventListener( static fn( $e )=> $e->tracer->setIndicesSource( new CollectUsing( Card::class ) ), eventAt: EventAt::Start )
			->inferFrom( BraintreeCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . '}', true );

		$this->assertInstanceOf( CollectUsing::class, $tracer->getIndicesSource() );

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage(
			sprintf( BraintreeCardTracer::USE_EVENT_LISTENER, BraintreeCardTracer::class . '::setIndicesSource', EventAt::class . '::' . EventAt::Start->name, 'set Card Type property names' )
		);

		( new BraintreeCardTracer() )->setIndicesSource( new CollectUsing( Card::class ) );
	}

	#[Test]
	#[DataProvider( 'provideInvalidSourceTypes' )]
	public function throwsExceptionIfPatternMatchFails( string|DOMElement $source, string $errorMsg, bool $afterIteration = false ): void {
		$tracer = new BraintreeCardTracer();

		$this->expectException( ScraperError::class );
		$this->expectExceptionMessage( $errorMsg );
		$tracer->inferFrom( $source, normalize: false );

		$afterIteration && $tracer->getData()->current();
	}

	/** @return mixed[] */
	public static function provideInvalidSourceTypes(): array {
		$validContent = file_get_contents( CardFactory::RESOURCE_PATH . '/cards.ts' ) ?: '';

		return [
			[ new DOMElement( 'invalid' ), BraintreeCardTracer::INVALID_SOURCE_TYPE ],
			[ 'invalid raw GitHub Content', BraintreeCardTracer::INVALID_SOURCE_TYPE ],
			// Valid but non-normalized content.
			[ $validContent, 'Braintree GitHub Card Type', true ],
			[ BraintreeCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . ' "test-card": "must-be-object enclosed by "{" and "}""', 'Braintree GitHub Card Type', true ],
			// Valid built-in credit card type pattern but value is not valid object. Regex matches nothing.
			[ BraintreeCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . ' card: { type: "valid", } },', 'Braintree GitHub Card\'s JS Object', true ],
			// Valid object but property name is invalid. Fails when reducing to Card properties.
			[
				BraintreeCardTracer::IGNORABLE_RAW_CONTENT_SEPARATOR . ' card: { type: "valid", invalidPropertyName: {} },',
				sprintf( BraintreeCardTracer::INVALID_CARD_PROPERTIES, 'niceType", "type", "patterns", "gaps", "lengths", "code', '. Property extraction source is :- type: "valid", invalidPropertyName: {}' ),
				true,
			],
		];
	}

	#[Test]
	public function ensuresCurrentIterationCountAndPropertyName(): void {
		$tracer     = new BraintreeCardTracer();
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

	/** @param array{0,int,1:string,2:string} $expected */
	public static function assertCurrentIterationProperty( BraintreeCardTracer $scope, array $expected, array $actual ): string {
		[ $count, $name, $value ] = $expected;

		self::assertSame( $count, $scope->getCurrentIterationCount() );
		self::assertSame( $name, $actual['property'] );
		self::assertSame( $value, $actual['value'] );

		return $value;
	}
}

// phpcs:disable Generic.Files.OneObjectStructurePerFile.MultipleFound

/** @template-implements Transformer<BraintreeCardTracer,mixed> */
class IterationAssertionTransformer implements Transformer {
	public function transform( string|array|DOMElement $element, object $scope ): mixed {
		// These assertions are made 6 times, for each Card property iteration.
		BraintreeTracerTest::assertInstanceOf( Card::class, $card = Card::from( $scope->getCurrentItemIndex() ) );
		BraintreeTracerTest::assertArrayHasKey( 'property', $element );
		BraintreeTracerTest::assertArrayHasKey( 'value', $element );

		return match ( $card ) {
			Card::Name       => BraintreeTracerTest::assertCurrentIterationProperty( $scope, [ 1,  'niceType', '"Maestro"' ], $element ),
			Card::Alias      => BraintreeTracerTest::assertCurrentIterationProperty( $scope, [ 2, 'type', '"maestro"' ], $element ),
			Card::Breakpoint => BraintreeTracerTest::assertCurrentIterationProperty( $scope, [ 4, 'gaps', '[4, 8, 12]' ], $element ),
			Card::Length     => BraintreeTracerTest::assertCurrentIterationProperty( $scope, [ 5, 'lengths', '[12, 13, 14-16, 17, 18, 19]' ], $element ),
			Card::Code       => BraintreeTracerTest::assertCurrentIterationProperty( $scope, [ 6, 'code', '{ name: "CVC", size: 3, }' ], $element ),
			Card::IINRange   => BraintreeTracerTest::assertCurrentIterationProperty(
				$scope,
				[ 3, 'patterns', '[ 493698, [500000, 504174], [504176, 506698], [506779, 508999], [56, 59], 63, 67, 6, ]' ],
				$element
			),
		};
	}
}
