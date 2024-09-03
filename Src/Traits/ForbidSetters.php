<?php
/**
 * Forbids Enum where setter methods are redundant.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard\Traits;

use LogicException;
use TheWebSolver\Codegarage\PaymentCard\Card;

/**
 * -----------------------------------------------------------------------------------------------
 * PaymentCardType Interface Stubs for Setter Methods.
 * -----------------------------------------------------------------------------------------------
 *
	 * This is intended to be used only inside an Enum because Setter methods are redundant.
	 * Enum must implement `TheWebSolver\Codegarage\Validator\Interfaces\PaymentCardType`.
	 *
	 * Values are calculated based on the PaymentCard case used. There are no properties for
	 * setting values for these setter methods. However, these must be implemented.
 */
trait ForbidSetters {
	abstract public function getName(): string;

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setName( string $name ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setAlias( string $alias ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setGap( string|int $gap, string|int ...$gaps ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setLength( array $value ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setCode( string $name, int $size ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	public function setPattern( array $value ): never {
		$this->setterIsForbidden( setter: __FUNCTION__ );
	}

	/** @throws LogicException Setter is forbidden for Payment Card Enum cases. */
	private function setterIsForbidden( string $setter ): never {
		$propName = Card::parsePropNameFrom( getterSetter: $setter );

		throw new LogicException( sprintf( 'Cannot set "%1$s" for enum case "%2$s".', $propName, $this->getName() ) );
	}
}
