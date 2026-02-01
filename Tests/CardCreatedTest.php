<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;

class CardCreatedTest extends TestCase {
	#[Test]
	public function itEnsuresPropagationSetterGetterWorks(): void {
		$event = ( new CardCreated( $this->createStub( CardType::class ), 0, null ) );

		$event->stopPropagation( true );

		$this->assertTrue( $event->shouldStopPropagation() );

		$event->stopPropagation( false );

		$this->assertFalse( $event->shouldStopPropagation() );
	}

	#[Test]
	public function itGetsCardNameEitherFromCardInstanceOrPayload(): void {
		$card = $this->createMock( CardType::class );

		$card->expects( $this->once() )->method( 'getName' )->willReturn( 'From Instance' );

		$event = new CardCreated( $card, 0, null );

		$this->assertSame( $card, $event->card );
		$this->assertSame( 'From Instance', $event->cardName() );

		$event = new CardCreated( null, 0, [ 'name' => 'From Payload' ] );

		$this->assertSame( 'From Payload', $event->cardName() );
	}

	#[Test]
	#[DataProvider( 'provideInvalidPayloadValues' )]
	public function itThrowsExceptionWhenGettingCardName( mixed $value ): void {
		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( sprintf( CardCreated::NO_OR_INVALID_NAME, 'key' ) );

		( new CardCreated( null, 'key', $value ) )->cardName();
	}

	/** @return mixed[] */
	public static function provideInvalidPayloadValues(): array {
		return [
			'Not an array'                           => [ null ],
			'Empty array'                            => [ [] ],
			'Without "name" index'                   => [ [ 'invalid' => 'payload' ] ],
			'"name" index but value not string type' => [ [ 'name' => NAN ] ],
		];
	}
}
