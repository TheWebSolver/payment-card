<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Enums;

enum Status {
	case Success;
	case Failure;
	case Omitted;
}
