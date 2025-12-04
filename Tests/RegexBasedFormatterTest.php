<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\Test;

use PHPUnit\Framework\TestCase;
use TheWebSolver\Codegarage\PaymentCard\CardInterface;
use TheWebSolver\Codegarage\PaymentCard\Traits\Mutator;
use TheWebSolver\Codegarage\Test\FormatterDataProvider;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

class RegexBasedFormatterTest extends TestCase {
	use FormatterDataProvider;

	protected function classWithTrait(): CardInterface {
		return new class() implements CardInterface {
			use RegexBasedFormatter, Mutator, Validator;
		};
	}
}
