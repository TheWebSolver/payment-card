<?php
/**
 * Payment Card base.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\CardInterface;
use TheWebSolver\Codegarage\PaymentCard\Traits\Validator;
use TheWebSolver\Codegarage\PaymentCard\Traits\RegexBasedFormatter;

abstract class CardType implements CardInterface {
	use Validator, RegexBasedFormatter;

	private string $name;
	private string $alias;

	/** @var array{0:string,1:int} */
	private array $code;

	/** @var (int|(int)[])[] */
	private array $length;

	/** @var (int|(int)[])[] */
	private array $pattern;

	public function __construct( private readonly CardHandler $card = new CardHandler() ) {
		$card->setType( name: $this->getType() );
	}

	abstract protected function getType(): string;

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

	public function getPattern(): array {
		return $this->pattern;
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
		$this->length = $this->card->resolveSizeWith( $value, forType: 'length' );

		return $this;
	}

	public function setPattern( array $value ): static {
		$this->pattern = $this->card->resolveSizeWith( $value, forType: 'pattern' );

		return $this;
	}
}
