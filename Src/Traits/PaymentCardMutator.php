<?php
declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;

trait PaymentCardMutator {
	use BreakpointGetter;

	private string $name;
	private string $alias;

	/** @var array{name:string,size:int} */
	private array $code;

	/** @var list<int|list<int>> */
	private array $length;

	/** @var list<int|list<int>> */
	private array $idRange;

	public function __construct(
		private readonly string $type,
		private readonly bool $checkLuhn = true,
		private readonly Asserter $asserter = new Asserter()
	) {}

	public function needsLuhnCheck(): bool {
		return $this->checkLuhn;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getName(): string {
		return $this->name;
	}

	public function getAlias(): string {
		return $this->alias;
	}

	public function getLength(): array {
		return $this->length;
	}

	public function getCode(): array {
		return $this->code;
	}

	public function getIdRange(): array {
		return $this->idRange;
	}

	public function setName( string $name ): static {
		$this->name = $name;

		return $this;
	}

	public function setAlias( string $alias ): static {
		$this->alias = $alias;

		return $this;
	}

	public function setCode( string $name, int $size ): static {
		$this->code = compact( 'name', 'size' );

		return $this;
	}

	public function setLength( array $value ): static {
		return $this->setSize( $value, prop: 'length' );
	}

	public function setIdRange( array $value ): static {
		return $this->setSize( $value, prop: 'idRange' );
	}

	/** @param mixed[] $value */
	private function setSize( array $value, string $prop ): static {
		$this->{$prop} = $this->asserter
			->setType( name: $this->getType() )
			->assertSizeWith( $value, forType: $prop );

		$this->asserter->resetType();

		return $this;
	}
}
