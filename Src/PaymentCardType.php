<?php
/**
 * Interface to define and validate Payment Card.
 *
 * @package TheWebSolver\Codegarage\Validation
 */

declare( strict_types = 1 );

namespace TheWebSolver\Codegarage\PaymentCard;

use RuntimeException;
use InvalidArgumentException;

interface PaymentCardType {
	public const GAP_CHECKSUM        = 12;
	public const GAP_HOLDERS         = '$1 $2 $3';
	public const GAP_DEFAULT_PATTERN = '(\d{4})(\d{4})(\d{4})';
	public const GAP_ALT_PATTERN     = '(\d{4})(\d{6})(\d{%d})';

	/**
	 * Gets the human readable card nice-name.
	 */
	public function getName(): string;

	/**
	 * Gets the card type/slug/alias.
	 */
	public function getAlias(): string;

	/**
	 * Gets the values where to put white spaces when formatting card.
	 *
	 * @return int[]
	 */
	public function getGap(): array;

	/**
	 * Gets the valid length for the card.
	 *
	 * @return (int|int[])[]
	 */
	public function getLength(): array;

	/**
	 * Gets the Security Code information and its valid length.
	 *
	 * @return array{0:string,1:int}
	 */
	public function getCode(): array;

	/**
	 * Gets the valid pattern for the card.
	 *
	 * @return (int|int[])[]
	 */
	public function getPattern(): array;

	/**
	 * Sets the human readable card nice-name.
	 */
	public function setName( string $name ): self;

	/**
	 * Sets the card type/slug/alias.
	 */
	public function setAlias( string $alias ): self;

	/**
	 * Sets the values where to put white spaces when formatting card.
	 */
	public function setGap( string|int $gap, string|int ...$gaps ): self;

	/**
	 * Sets the valid length for the card.
	 *
	 * @param (string|int|(string|int)[])[] $value
	 * @throws InvalidArgumentException When $value is empty, or provided $value not as per expected type.
	 */
	public function setLength( array $value ): self;

	/**
	 * Sets the Security Code information and its valid length.
	 */
	public function setCode( string $name, int $size ): self;

	/**
	 * Sets the valid pattern for the card.
	 *
	 * @param (string|int|(string|int)[])[] $value
	 * @throws InvalidArgumentException When $value is empty, or provided $value not as per expected type.
	 */
	public function setPattern( array $value ): self;

	/**
	 * Formats given payment card number with gaps provided.
	 *
	 * @throws RuntimeException When given Card Number could not be formatted.
	 */
	public function format( string|int $cardNumber ): string;

	/**
	 * Validates the given card number.
	 *
	 * For validation with Luhn Algorithm, that package must be installed. If it is not installed,
	 * there is no actual validation being performed and thus Luhn validation will always pass.
	 *
	 * @link https://github.com/thewebsolver/luhn-algorithm
	 */
	public function isNumberValid( mixed $subject, bool $withLuhnAlgorithm ): bool;

	/**
	 * Validates the given card security code.
	 */
	public function isCodeValid( mixed $subject ): bool;
}
