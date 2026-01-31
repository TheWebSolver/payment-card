<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Enums;

enum Status {
	case Success;
	case Failure;
	case Omitted;

	public function resolvedState(): string {
		return match ( $this ) {
			self::Success => 'Resolved',
			self::Failure => 'Could not resolve',
			self::Omitted => 'Skipped resolving',
		};
	}
}
