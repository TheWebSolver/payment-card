<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use LogicException;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\ForbidSetters;

class SetterForbidderTest extends TestCase {
	#[DataProvider( 'provideForbiddenSetterMethods' )]
	public function testForbidden( array $args, string $methodName, string $propName ): void {
		$class = new class() {
			use ForbidSetters;

			public function getName(): string {
				return 'Test';
			}
		};

		$this->expectException( LogicException::class );
		$this->expectExceptionMessage( "Cannot set property \"{$propName}\" for the \"Test\" card." );
		$class->{$methodName}( ...$args );
	}

	public static function provideForbiddenSetterMethods(): array {
		return array(
			array( array( '' ), 'setName', 'name' ),
			array( array( '' ), 'setAlias', 'alias' ),
			array( array( '', 0 ), 'setCode', 'code' ),
			array( array( array( 0 ) ), 'setIdRange', 'idRange' ),
			array( array( array( 0 ) ), 'setLength', 'length' ),
			array( array( 0 ), 'setBreakpoint', 'breakpoint' ),
		);
	}
}
