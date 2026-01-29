<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\Traits\PaymentCardMutator;
use TheWebSolver\Codegarage\PaymentCard\Interfaces\PaymentCardType;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;
use TheWebSolver\Codegarage\PaymentCard\Traits\PaymentCardValidator;

class PaymentCard implements PaymentCardType {
	use PaymentCardMutator, PaymentCardValidator, RegexBasedFormatter;
}
