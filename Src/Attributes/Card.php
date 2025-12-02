<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Attributes;

enum Card: string {
	case Name       = 'name';
	case Alias      = 'alias';
	case Breakpoint = 'breakpoint';
	case Length     = 'length';
	case IINRange   = 'id-range';
	case Code       = 'code';
	case Status     = 'status';
	case Validator  = 'validator';
}
