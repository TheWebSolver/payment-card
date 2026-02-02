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
use TheWebSolver\Codegarage\PaymentCard\Event\CardResolving;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardType;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\CardFactory;

class CardResolvingTest extends TestCase {
	#[Test]
	public function itVerifiesPropertiesSet(): void {
		$resolveEvent = new CardResolving(
			factory: $this->createStub( CardFactory::class ),
			factoryNumber: 1,
			cardNumber: '0',
			state: new CardCreated( $this->createStub( CardType::class ), 0, 0, false ) // @phpstan-ignore-line
		);

		$this->assertInstanceOf( Stub::class, $resolveEvent->factory );
		$this->assertSame( 1, $resolveEvent->factoryNumber );
		$this->assertSame( '0', $resolveEvent->cardNumber );
		$this->assertTrue( $resolveEvent->started() );
		$this->assertTrue( $resolveEvent->processing() );
		$this->assertFalse( $resolveEvent->finished() );
		$this->assertFalse( $resolveEvent->isSuccess() );
		$this->assertInstanceOf( CardCreated::class, $resolveEvent->current() );
	}

	#[Test]
	public function itEnsuresActionIsSuccessfulBasedOnCurrent(): void {
		$factory = $this->createMock( CardFactory::class );
		$event   = new CardResolving( $factory, 0, '0', null );

		$this->assertFalse( $event->started() );
		$this->assertFalse( $event->processing() );
		$this->assertFalse( $event->finished() );

		$factory->expects( $invokeMocker = $this->exactly( 4 ) )
		->method( 'getPayload' )
		->willReturnCallback( fn () => [ $invokeMocker->numberOfInvocations() => [ 'name' => 'Test Card' ] ] );

		$state = new CardCreated( $this->createStub( CardType::class ), 4 /* Last payload index */, 'Test Card' );
		$event = new CardResolving( $factory, 0, '123', $state );

		$this->assertTrue( $event->started() );
		$this->assertTrue( $event->processing() );

		foreach ( Status::cases() as $status ) {
			$this->assertFalse( $event->isSuccess(), "Success when status given. Always false for Status::{$status->name}" );
		}

		$this->assertFalse( $event->finished(), 'Invoked "getPayload" #1' );
		$this->assertFalse( $event->finished(), 'Invoked "getPayload" #2' );
		$this->assertFalse( $event->finished(), 'Invoked "getPayload" #3' );
		$this->assertTrue( $event->finished(), 'Invoked "getPayload" #4' );
	}

	#[Test]
	#[DataProvider( 'provideThrowableMethodNames' )]
	public function itThrowsExceptionOnDirectMethodInvocation( string $methodName, string $expectedMsg ): void {
		$resolveEvent = new CardResolving( $this->createStub( CardFactory::class ), 0, '0', null );

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( sprintf( $expectedMsg, 0 ) );
		$resolveEvent->{$methodName}();
	}

	/** @return string[][] */
	public static function provideThrowableMethodNames(): array {
		return [
			[ 'current', CardResolving::CURRENT_CARD_ERROR ],
			[ 'resourceInfo', CardResolving::RESOURCE_ERROR ],
		];
	}

	#[Test]
	#[DataProvider( 'provideResourcePathForFactory' )]
	public function itGetsInfoAboutResourcePathFromFactory( ?string $resourcePath, bool $expectedValidPath ): void {
		$factory = $this->createMock( CardFactory::class );

		$factory->expects( $this->once() )->method( 'getResourcePath' )->willReturn( $resourcePath );

		if ( ! $expectedValidPath ) {
			$this->expectException( LogicException::class );
			$this->expectExceptionMessage( sprintf( CardResolving::RESOURCE_ERROR, 1 ) );
		}

		$this->assertSame(
			sprintf( CardResolving::RESOURCE_INFO, $resourcePath ?? '' ),
			( new CardResolving( $factory, 1, '0', null ) )->resourceInfo()
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
			sprintf( CardResolving::FACTORY_STATUS_INFO, 'Started', '12345', 0 ),
			( new CardResolving( $factory, 0, '12345', null ) )->factoryStatusInfo()
		);

		foreach ( Status::cases() as $status ) {
			$this->assertSame(
				sprintf( CardResolving::FACTORY_STATUS_INFO, 'Finished', '6789', 1 ),
				( new CardResolving( $factory, 1, '6789', $status ) )->factoryStatusInfo(),
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
				sprintf( CardResolving::FACTORY_RESOLVED_INFO, $isResolved, 0 ),
				( new CardResolving( $factory, 0, '1', $status ) )->factoryResolvedInfo()
			);
		}
	}

	#[Test]
	public function itGetsInfoAboutCardResolved(): void {
		$factory = $this->createStub( CardFactory::class );
		$card    = $this->createMock( CardType::class );

		$card->expects( $this->exactly( 3 ) )->method( 'getName' )->willReturn( 'Test Card' );

		// @phpstan-ignore-next-line
		$event = new CardResolving( $factory, 0, '0', state: new CardCreated( $card, 0, [], true ) );
		$info  = [
			'Resolved'          => Status::Success,
			'Could not resolve' => Status::Failure,
			'Skipped resolving' => Status::Omitted,
		];

		foreach ( $info as $status => $case ) {
			$this->assertSame(
				sprintf( CardResolving::CARD_RESOLVED_INFO, $status, 'Test Card' ),
				$event->cardResolvedInfo( $case )
			);
		}
	}
}
