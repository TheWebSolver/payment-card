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

interface CardInterface {
	public const BREAKPOINT_CHECKSUM        = 12;
	public const BREAKPOINT_HOLDERS         = '$1 $2 $3';
	public const BREAKPOINT_DEFAULT_PATTERN = '(\d{4})(\d{4})(\d{4})';
	public const BREAKPOINT_ALT_PATTERN     = '(\d{4})(\d{6})(\d{%d})';

	/**
	 * Gets the card type such as Debit Card, Credit Card, etc.
	 */
	public function getType(): string;

	/**
	 * Gets the card human readable nice-name.
	 */
	public function getName(): string;

	/**
	 * Gets the card type/slug/alias.
	 */
	public function getAlias(): string;

	/**
	 * Gets the card breakpoint values.
	 *
	 * @return int[]
	 */
	public function getBreakpoint(): array;

	/**
	 * Gets the card valid length.
	 *
	 * @return (int|int[])[]
	 */
	public function getLength(): array;

	/**
	 * Gets the card Security Code information and its valid length.
	 *
	 * @return array{0:string,1:int}
	 */
	public function getCode(): array;

	/**
	 * Gets the card valid Identification Number range.
	 *
	 * @return (int|int[])[]
	 */
	public function getIdRange(): array;

	/**
	 * Sets the card human readable nice-name.
	 */
	public function setName( string $name ): self;

	/**
	 * Sets the card type/slug/alias.
	 */
	public function setAlias( string $alias ): self;

	/**
	 * Sets the card breakpoint values.
	 */
	public function setBreakpoint( string|int $number, string|int ...$numbers ): self;

	/**
	 * Sets the card valid length.
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
	 * Sets the card valid Identification Number range.
	 *
	 * @param (string|int|(string|int)[])[] $value
	 * @throws InvalidArgumentException When $value is empty, or provided $value not as per expected type.
	 */
	public function setIdRange( array $value ): self;

	/**
	 * Formats given card number with breakpoints provided.
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
