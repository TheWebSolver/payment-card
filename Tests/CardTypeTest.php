<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Asserter;
use TheWebSolver\Codegarage\Test\Fixture\Formatter;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardType;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;

class PaymentCardTypeTest extends TestCase {
	#[Test]
	public function itEnsuresSetterGetterWorks(): void {
		$cardType = new PaymentCardType( PaymentCardFactory::DEBIT_CARD, false, $this->createStub( Asserter::class ) );

		$this->assertSame( PaymentCardFactory::DEBIT_CARD, $cardType->getType() );
		$this->assertFalse( $cardType->needsLuhnCheck() );

		$cardType = new PaymentCardType( asserter: $asserter = $this->createMock( Asserter::class ) );

		$asserter->expects( $this->exactly( 2 ) )->method( 'setType' )->with( PaymentCardFactory::CREDIT_CARD )->willReturnSelf();

		// Suppress validating arguments passed to length and ID Range setter methods to omit throwing any surprise exception.
		$asserter->expects( $this->exactly( 2 ) )->method( 'assertSizeWith' )->willReturnCallback(
			static fn ( array $valuePassedToSetterMethod, string $lengthOrIdRange ) => $valuePassedToSetterMethod
		);

		$this->assertSame( PaymentCardFactory::CREDIT_CARD, $cardType->getType() );
		$this->assertTrue( $cardType->needsLuhnCheck() );

		$this->assertSame( 'Test', $cardType->setName( 'Test' )->getName() );
		$this->assertSame( 'test', $cardType->setAlias( 'test' )->getAlias() );
		$this->assertSame( [ 4, 8 ], $cardType->setBreakpoint( 4, 8 )->getBreakpoint() );
		$this->assertSame(
			[
				'name' => 'CVC',
				'size' => 3,
			],
			$cardType->setCode( 'CVC', 3 )->getCode()
		);
		$this->assertSame( [ 12, [ 16, 19 ] ], $cardType->setLength( [ 12, [ 16, 19 ] ] )->getLength() );
		$this->assertSame( [ 60, [ 45, 50 ], 80 ], $cardType->setIdRange( [ 60, [ 45,50 ], 80 ] )->getIdRange() );
	}

	#[Test]
	#[DataProvider( 'provideNumericValuesForValidation' )]
	public function itEnsuresCardLengthAndIdRangeAreValidatedWithAsserter(
		array $setterValue,
		string $type,
		bool $throws = true,
		?array $getterValue = null
	): void {
		$cardType = new PaymentCardType();
		$setter   = "set{$type}";
		$getter   = "get{$type}";

		$throws && $this->expectException( InvalidArgumentException::class );

		$this->assertSame( $getterValue ?? $setterValue, $cardType->{$setter}( $setterValue )->{$getter}() );
	}

	public static function provideNumericValuesForValidation(): array {
		return [
			[ [ 12, [ 16, 19 ], 20 ], 'Length', false ],
			[ [ 12, [ 16, 19 ], 20 ], 'IdRange', false ],
			[ [], 'Length', true ],
			[ [], 'IdRange', true ],
			[ [ 12, [ 15, 22 ], '25' ], 'Length', false, [ 12, [ 15, 22 ], 25 ] ],
			[ [ 12, [ 15, 22 ], '25' ], 'IdRange', false, [ 12, [ 15, 22 ], 25 ] ],
			[ [ 'either 1nt or num3ric string' ], 'Length', true ],
			[ [ 'either 1nt or num3ric string' ], 'IdRange', true ],
		];
	}

	#[Test]
	#[DataProviderExternal( Formatter::class, 'provideCardNumberWithBreakpoints' )]
	public function itFormatsCardNumberBasedOnBreakpoint( array $breakpoints, string|int $cardNumber, string $expected ): void {
		$this->assertSame( $expected, ( new PaymentCardType() )->setBreakpoint( ...$breakpoints )->format( $cardNumber ) );
	}
}
