<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\Test\FormatterDataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

class RegexBasedFormatterTest extends TestCase {
	use FormatterDataProvider;

	protected function classWithTrait(): object {
		return new class() {
			use RegexBasedFormatter;
		};
	}
}
