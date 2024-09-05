<?php
/**
 * Queue based formatter Test.
 *
 * @package TheWebSolver\Codegarage\Test
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\Traits\QueueBasedFormatter;

class QueueBasedFormatterTest extends TestCase {
	use FormatterDataProvider;

	protected function classWithTrait(): object {
		return new class() {
			use QueueBasedFormatter;
		};
	}
}
