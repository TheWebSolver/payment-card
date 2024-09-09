<?php
/**
 * Card Interface setter methods.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use TheWebSolver\Codegarage\PaymentCard\Asserter;
use TheWebSolver\Codegarage\PaymentCard\CardFactory;

trait Mutator {
	use BreakpointGetter;

	private string $name;
	private string $alias;

	/** @var array{0:string,1:int} */
	private array $code;

	/** @var (int|(int)[])[] */
	private array $length;

	/** @var (int|(int)[])[] */
	private array $idRange;

	public function __construct(
		private readonly string $type = CardFactory::CREDIT_CARD,
		private readonly Asserter $asserter = new Asserter()
	) {
		$asserter->setType( name: $this->getType() );
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
		$this->code = array( $name, $size );

		return $this;
	}

	public function setLength( array $value ): static {
		$this->length = $this->asserter->assertSizeWith( $value, forType: 'length' );

		return $this;
	}

	public function setIdRange( array $value ): static {
		$this->idRange = $this->asserter->assertSizeWith( $value, forType: 'idRange' );

		return $this;
	}
}
