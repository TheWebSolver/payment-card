<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\CardInterface;
use TheWebSolver\Codegarage\PaymentCard\Traits\Mutator;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;
use TheWebSolver\Codegarage\PaymentCard\Traits\QueueBasedFormatter;

class QueueBasedFormatterTest extends TestCase {
	use FormatterDataProvider;

	protected function classWithTrait(): CardInterface {
		return new class() implements CardInterface {
			use QueueBasedFormatter, Mutator, Validator;
		};
	}
}
