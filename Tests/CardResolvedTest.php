<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Enums\Status;
use TheWebSolver\Codegarage\PaymentCard\Event\CardCreated;
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolved;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;

class CardResolvedTest extends TestCase {
	#[Test]
	public function itVerifiesPropertiesSet(): void {
		$resolveEvent = new CardResolved(
			factory: $this->createStub( CardFactory::class ),
			factoryNumber: 1,
			cardNumber: '0',
			status: null,
			current: new CardCreated( $this->createStub( CardType::class ), 0, 0, false ) // @phpstan-ignore-line
		);

		$this->assertInstanceOf( Stub::class, $resolveEvent->factory );
		$this->assertSame( 1, $resolveEvent->factoryNumber );
		$this->assertSame( '0', $resolveEvent->cardNumber );
		$this->assertFalse( $resolveEvent->started() );
		$this->assertFalse( $resolveEvent->isCreating() );
		$this->assertFalse( $resolveEvent->finished() );
		$this->assertFalse( $resolveEvent->isSuccess() );
		$this->assertInstanceOf( CardCreated::class, $resolveEvent->current() );
	}

	#[Test]
	public function itEnsuresActionIsSuccessfulBasedOnStatus(): void {
		$factory          = $this->createMock( CardFactory::class );
		$nonCreatingEvent = new CardResolved( $factory, 0, '0', Status::Omitted, null );

		$this->assertFalse( $nonCreatingEvent->isCreating() );
		$this->assertFalse( $nonCreatingEvent->finished() );

		$current = new CardCreated( $this->createStub( CardType::class ), 2, 'Test Card', true );

		$factory->expects( $invokeMocker = $this->exactly( 2 ) )
			->method( 'getPayload' )
			->willReturnCallback( fn () => [ $invokeMocker->numberOfInvocations() => 'Test Card' ] );

		$creatingEvent = new CardResolved( $factory, 0, '123', Status::Omitted, $current );

		$this->assertTrue( $creatingEvent->isCreating() );
		$this->assertFalse( $creatingEvent->finished() ); // Invoked "getPayload" #1.

		foreach ( Status::cases() as $status ) {
			$resolveEvent = new CardResolved( $factory, 0, '456', $status, $current );

			$this->assertTrue( $resolveEvent->started() );
			$this->assertSame( Status::Omitted === $status ? true : false, $resolveEvent->isCreating() );
			$this->assertSame( Status::Success === $status ? true : false, $resolveEvent->isSuccess() );
			$this->assertSame(
				Status::Omitted === $status ? true : false,
				$resolveEvent->finished(),
				"Finished resolving when in omitted status and factory's last payload index matches created card's payload index"
			); // Invoked "getPayload" #2.
		}
	}

	#[Test]
	#[DataProvider( 'provideThrowableMethodNames' )]
	public function itThrowsExceptionOnDirectMethodInvocation( string $methodName, string $expectedMsg ): void {
		$resolveEvent = new CardResolved( $this->createStub( CardFactory::class ), 0, '0' );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( sprintf( $expectedMsg, 0 ) );
		$resolveEvent->{$methodName}();
	}

	/** @return string[][] */
	public static function provideThrowableMethodNames(): array {
		return [
			[ 'current', CardResolved::CURRENT_CARD_ERROR ],
			[ 'currentCardName', CardResolved::CURRENT_CARD_ERROR ],
			[ 'resourceInfo', CardResolved::RESOURCE_ERROR ],
		];
	}

	#[Test]
	public function itGetsCurrentCardNameEitherFromCardInstanceOrPayloadData(): void {
		$factory = $this->createStub( CardFactory::class );
		$card    = $this->createMock( CardType::class );

		$card->expects( $this->once() )->method( 'getName' )->willReturn( 'Created Card' );

		$cardCreated = new CardCreated( $card, 0, [], true );
		$createEvent = new CardResolved( $factory, 0, '0', Status::Success, $cardCreated );

		$this->assertSame( 'Created Card', $createEvent->currentCardName(), 'From $cardCreated->card->getName()' );

		$cardNotCreated = new CardCreated( $card, 0, [ 'name' => 'Payload Card' ], false );
		$nonCreateEvent = new CardResolved( $factory, 0, '0', Status::Failure, $cardNotCreated );

		$this->assertSame( 'Payload Card', $nonCreateEvent->currentCardName(), 'From $cardNotCreated->payloadValue' );
	}

	/** @param ?CardCreated<CardType> $current */
	#[Test]
	#[DataProvider( 'provideInvalidEventForCurrentCardName' )]
	public function itThrowsExceptionForCurrentCardNameWhenNoEventOrEventPropertiesMismatch(
		?CardCreated $current,
		string $expectedMsg = CardResolved::PAYLOAD_ERROR
	): void {
		$factory      = $this->createStub( CardFactory::class );
		$resolveEvent = new CardResolved( $factory, 0, '0', Status::Success, $current );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( sprintf( $expectedMsg, 0 ) );

		$resolveEvent->currentCardName();
	}

	/** @return mixed[] */
	public static function provideInvalidEventForCurrentCardName(): array {
		$card = self::createStub( CardType::class );

		return [
			[ null, CardResolved::CURRENT_CARD_ERROR ],
			[ new CardCreated( null /* Not created even though it is set as creatable */, '', [], true ) ],
			[ new CardCreated( $card, 'card-key', 'payload data must be an array', false ) ],
			[ new CardCreated( $card, 'card-key', [ 'no-"name"-key' => 'Card Name' ], false ) ],
			[ new CardCreated( $card, 'card-key', [ 'name' => 123 /* Payload's "name" key must have a string value */ ], false ) ],
		];
	}

	#[Test]
	#[DataProvider( 'provideStatusBasedStringInfo' )]
	public function itVerifiesResolvedToString( Status $status, string $expectedString ): void {
		$this->assertSame( $expectedString, CardResolved::resolvedToString( $status ) );
	}

	/** @return array<array{Status,string}> */
	public static function provideStatusBasedStringInfo(): array {
		return [
			[ Status::Success, 'Resolved' ],
			[ Status::Failure, 'Could not resolve' ],
			[ Status::Omitted, 'Skipped resolving' ],
		];
	}

	#[Test]
	#[DataProvider( 'provideResourcePathForFactory' )]
	public function itGetsInfoAboutResourcePathFromFactory( ?string $resourcePath, bool $expectedValidPath ): void {
		$factory = $this->createMock( CardFactory::class );

		$factory->expects( $this->once() )->method( 'getResourcePath' )->willReturn( $resourcePath );

		if ( ! $expectedValidPath ) {
			$this->expectException( LogicException::class );
			$this->expectExceptionMessage( sprintf( CardResolved::RESOURCE_ERROR, 1 ) );
		}

		$this->assertSame(
			sprintf( CardResolved::RESOURCE_INFO, $resourcePath ?? '' ),
			( new CardResolved( $factory, 1, '0' ) )->resourceInfo()
		);
	}

	/** @return array<array{?string,bool}> */
	public static function provideResourcePathForFactory(): array {
		return [
			[ null, false ],
			[ '', false ],
			[ 'invalid/resource/path', false ],
			[ __DIR__, true ],
		];
	}

	#[Test]
	public function itGetsInfoAboutFactoryStatus(): void {
		$factory = $this->createStub( CardFactory::class );

		$this->assertSame(
			sprintf( CardResolved::FACTORY_STATUS_INFO, 'Started', '12345', 0 ),
			( new CardResolved( $factory, 0, '12345', null ) )->factoryStatusInfo()
		);

		foreach ( Status::cases() as $status ) {
			$this->assertSame(
				sprintf( CardResolved::FACTORY_STATUS_INFO, 'Finished', '6789', 1 ),
				( new CardResolved( $factory, 1, '6789', $status ) )->factoryStatusInfo(),
				'Always returns "Finished" info when status is not null'
			);
		}
	}

	#[Test]
	public function itGetsInfoAboutFactoryResolved(): void {
		$factory = $this->createStub( CardFactory::class );

		foreach ( [ null, ...Status::cases() ] as $status ) {
			$isResolved = Status::Success === $status ? 'Resolved' : 'Could not resolve';

			$this->assertSame(
				sprintf( CardResolved::FACTORY_RESOLVED_INFO, $isResolved, 0 ),
				( new CardResolved( $factory, 0, '1', $status ) )->factoryResolvedInfo()
			);
		}
	}

	#[Test]
	public function itGetsInfoAboutCardResolved(): void {
		$factory = $this->createStub( CardFactory::class );
		$card    = $this->createMock( CardType::class );

		$card->expects( $this->exactly( 3 ) )->method( 'getName' )->willReturn( 'Test Card' );

		// @phpstan-ignore-next-line
		$event = new CardResolved( $factory, 0, '0', current: new CardCreated( $card, 0, [], true ) );
		$info  = [
			'Resolved'          => Status::Success,
			'Could not resolve' => Status::Failure,
			'Skipped resolving' => Status::Omitted,
		];

		foreach ( $info as $status => $case ) {
			$this->assertSame(
				sprintf( CardResolved::CARD_RESOLVED_INFO, $status, 'Test Card' ),
				$event->cardResolvedInfo( $case )
			);
		}
	}
}
