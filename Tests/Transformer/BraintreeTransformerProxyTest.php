<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use DOMElement;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Enums\Card;
use TheWebSolver\Codegarage\PaymentCard\Tracer\BraintreeCardTracer;
use TheWebSolver\Codegarage\PaymentCard\Proxy\BraintreeTransformerProxy;

class BraintreeTransformerProxyTest extends TestCase {
	/** @param mixed[] $element */
	#[Test]
	#[DataProvider( 'providePropertyKeyOrIndex' )]
	public function itThrowsExceptionWhenNeitherPropertyKeyNorIndexGiven( array $element, ?string $propertyName, mixed $expected, string $throws = '' ): void {
		$scope = $this->createMock( BraintreeCardTracer::class );
		$proxy = new BraintreeTransformerProxy();

		$scope->expects( $this->once() )->method( 'getCurrentItemIndex' )->willReturn( $propertyName );

		if ( $throws ) {
			$this->expectExceptionMessage( $throws );
		}

		$this->assertSame( $expected, $proxy->transform( $element, $scope ) );
	}

	public static function providePropertyKeyOrIndex(): array {
		return [
			[ [ 'value' => '"Visa"' ], Card::Name->value, 'Visa' ],
			[
				[
					'property' => 'niceType',
					'value'    => '"Visa"',
				],
				null,
				'Visa',
			],
			[ [ 'value' => '"Visa"' ], null, '', BraintreeTransformerProxy::MISSING_PROPERTY_KEY ],
			[ [ 'value' => '"Visa"' ], 'InvalidCaseValue', '', '"InvalidCaseValue" is not a valid backing value' ],
		];
	}

	/** @param string|mixed[]|DOMElement $element */
	#[Test]
	#[DataProvider( 'provideElementsToTransform' )]
	public function itTransformsElementByCardProperty( string|array|DOMElement $element, string $propertyName, mixed $expected, string $throws = '' ): void {
		$proxy = new BraintreeTransformerProxy();
		$scope = $this->createMock( BraintreeCardTracer::class );

		if ( $throws ) {
			$this->expectExceptionMessage( $throws );
		} else {
			$scope->expects( $this->exactly( 2 ) )->method( 'getCurrentItemIndex' )->willReturn( $propertyName, null );
		}

		$this->assertSame( $expected, $proxy->transform( $element, $scope ) );
		$this->assertSame( $expected, $proxy->transform( $element, $scope ) );
	}

	/** @return mixed[] */
	public static function provideElementsToTransform(): array {
		return [
			[
				[
					'property' => 'niceType',
					'value'    => 'Visa',
				],
				Card::Name->value,
				'Visa',
			],
			[
				[
					'property' => 'type',
					'value'    => 'visa',
				],
				Card::Alias->value,
				'visa',
			],
			[
				[
					'property' => 'patterns',
					'value'    => '[2, 4-6, [8,10,12], 14]',
				],
				Card::IINRange->value,
				[ 2, [ 4, 6 ], [ 8, 10, 12 ], 14 ],
			],
			[
				[
					'property' => 'gaps',
					'value'    => '[4, 8, 12]',
				],
				Card::Breakpoint->value,
				[ 4, 8, 12 ],
			],
			[
				[
					'property' => 'lengths',
					'value'    => '[12, 16]',
				],
				Card::Length->value,
				[ 12, 16 ],
			],
			[
				[
					'property' => 'code',
					'value'    => '{name: "CVC", size: 3}',
				],
				Card::Code->value,
				[
					'name' => 'CVC',
					'size' => 3,
				],
			],
			[ '', '', '', 'Invalid element type provided to transform Braintree GitHub Card Type. "string" type given.' ],
			[ [], '', '', 'Invalid element type provided to transform Braintree GitHub Card Type. "array" type given.' ],
			[ new DOMElement( 'div' ), '', '', 'Invalid element type provided to transform Braintree GitHub Card Type. "DOMElement" type given.' ],
			[ [ 'values' => '' ], '', '', 'Invalid element type provided to transform Braintree GitHub Card Type. "array" type given.' ],

		];
	}
}
