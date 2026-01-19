<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

trait CardTypeMutator {
	private string $name;
	private string $type;

	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function setName( string $name ): static {
		$this->name = $name;

		return $this;
	}
}
