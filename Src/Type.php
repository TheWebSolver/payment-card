<?php
/**
 * Payment Card base.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use TheWebSolver\Codegarage\PaymentCard\PaymentCardType;
use TheWebSolver\Codegarage\PaymentCard\Traits\Asserter;

abstract class Type implements PaymentCardType {
	use Asserter;

	private string $name;
	private string $alias;

	/** @var array{0:string,1:int} */
	private array $code;

	/** @var (int|(int)[])[] */
	private array $length;

	/** @var array{pattern:string,replacement:string,valid:int[],checksum:int} */
	public array $gap;

	/** @var (int|(int)[])[] */
	private array $pattern;

	public function __construct( private readonly Card $card = new Card() ) {
		$card->setCardType( name: $this->getCardType() );
	}

	abstract protected function getCardType(): string;

	public function getName(): string {
		return $this->name;
	}

	public function getAlias(): string {
		return $this->alias;
	}

	public function getGap(): array {
		return $this->gap['valid'];
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

	public function setGap( string|int $gap, string|int ...$gaps ): static {
		$this->card->isProcessing( name: 'gap' );

		$this->gap = $this->card->parseGap( array( $gap, ...$gaps ) );

		return $this;
	}

	public function setLength( array $value ): static {
		return $this->setSizesFor( $value, setter: __FUNCTION__ );
	}

	public function setCode( string $name, int $size ): static {
		$this->code = array( $name, $size );

		return $this;
	}

	public function setPattern( array $value ): static {
		return $this->setSizesFor( $value, setter: __FUNCTION__ );
	}

	public function format( string|int $cardNumber ): string {
		return $this->card->format( $cardNumber, gap: $this->gap );
	}

	/** @param mixed[] $value */
	private function setSizesFor( array $value, string $setter ): static {
		$this->card->assertNotEmpty( $value );
		$this->card->isProcessing( $name = $this->card->parsePropNameFrom( getterSetter: $setter ) );

		array_walk( array: $value, callback: $this->card->assertHasSize( ... ) );

		$this->{$name} = $value;

		return $this;
	}
}
