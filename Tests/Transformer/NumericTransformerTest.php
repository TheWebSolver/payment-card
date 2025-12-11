<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test\Transformer;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\Attributes\Test;
use TheWebSolver\Codegarage\PaymentCard\Transformer\NumericTransformer;

class NumericTransformerTest extends TestCase {
	#[Test]
	public function testDigitExtraction(): void {
		$string = '[775557777-8688889999, 45, 99, 5-6, [12,13,14], 622126–622925 (China UnionPay co-branded), 6011, 644-649, 65, 60400100–60420099, 353, 356 (RuPay-JCB co-branded)]';

		$extracted = ( new NumericTransformer() )->transform( $string, $this->createStub( self::class ) );

		$this->assertNotEmpty( $extracted );

		$this->assertSame(
			[ [ 775557777,8688889999 ],45,99,[ 5,6 ],[ 12,13,14 ],[ 622126,622925 ],6011,[ 644,649 ],65,[ 60400100,60420099 ],353,356 ],
			$extracted
		);
	}
}
