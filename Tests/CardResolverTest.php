<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\CardResolver;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\PaymentCardFactory;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\ResolvesCard;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardValidationAction;

class CardResolverTest extends TestCase {
	public const DOMESTIC_CARDS = [
		'dnc' => [
			'name'       => 'Dummy Nepal Card',
			'alias'      => 'dnc',
			'type'       => 'Debit Card',
			'code'       => [
				'name' => 'CVC',
				'size' => 3,
			],
			'breakpoint' => [ 4, 8, 12 ],
			'length'     => [ 16, 18, 19 ],
			'idRange'    => [ 50, 64, [ 90, 93 ] ],
		],
	];

	private ResolvesCard $resolver;
	private CardValidationAction&MockObject $handler;

	public function setUp(): void {
		$this->handler  = $this->createMock( CardValidationAction::class );
		$this->resolver = ( new CardResolver() )->using(
			new PaymentCardFactory( self::DOMESTIC_CARDS ),
			new PaymentCardFactory( PaymentCardFactory::RESOURCE_PATH . DIRECTORY_SEPARATOR . 'paymentCards.json' )
		);

		$this->handler->expects( $this->once() )->method( 'with' )->with( $this->resolver );
	}

	public function tearDown(): void {
		unset( $this->resolver, $this->handler );
	}

	#[Test]
	#[DataProvider( 'provideNumbersForExit' )]
	public function itResolvesEitherCardOrNullWhenExitStatusIsTrue( string $number, ?string $expectedName, int $handleInvokeCount ): void {
		$this->handler->expects( $this->exactly( $handleInvokeCount ) )
			->method( 'handle' )
			->willReturnCallback( fn( CardCreated $event ) => $this->resolver->validate( $event ) );

		$resolvedCard = $this->resolver->when( $number )->resolve( $this->handler );

		$this->assertIsNotArray( $resolvedCard );
		$this->assertSame( $expectedName, $resolvedCard?->getName() );
	}

	/** @return mixed[] */
	public static function provideNumbersForExit(): array {
		return [
			[ 'non-a-number', null, 11 ],
			[ '9792030000000000', 'Troy', 5 ],
			[ '6011277750635920', 'Discover', 6 ],
			[ '6500830000000002', 'Troy', 5 ],
			[ '6460435912011101', 'Dummy Nepal Card', 1 ],
		];
	}

	#[Test]
	public function itResolvesEitherCardOrNullWhenExitStatusIsFalse(): void {
		$this->handler->expects( $this->exactly( 11 ) ) // 1: domestic card, 10: cards from resource path.
			->method( 'handle' )
			->willReturnCallback( fn( CardCreated $event ) => $this->resolver->validate( $event ) );

		$resolvedCards = $this->resolver->when( '6460435912011101', exitOnResolve: false )->resolve( $this->handler );

		$this->assertIsArray( $resolvedCards, 'Uses both factories payload to resolve card.' );
		$this->assertSame( 'Dummy Nepal Card', $resolvedCards[0][0]->getName(), 'Matches "64" from Dummy payload' );
		$this->assertFalse( isset( $resolvedCards[0][1] ) );
		$this->assertSame( 'Discover', $resolvedCards[1][0]->getName(), 'Matches "64" from Discover payload' );
		$this->assertFalse( isset( $resolvedCards[1][1] ) );
	}
}
