<?php
/**
 * The Payment Cards from a Class.
 *
 * @package TheWebSolver/Codegarage/Test
 */

 use TheWebSolver\Codegarage\Test\Resource\NapasCard;

return new class() {
	/** @return array<string,mixed[]> */
	public function __invoke() {
		return array(
			'napas' => $this->napasCardSchema(),
			'gpn'   => $this->gpnCardSchema(),
			'humo'  => $this->humoCardSchema(),
		);
	}

	/** @return array<string,mixed> */
	private function napasCardSchema(): array {
		return array(
			'name'       => 'Napas',
			'alias'      => 'napas',
			'classname'  => NapasCard::class,
			'breakpoint' => array( 4, 8, 12 ),
			'code'       => array( 'CVC', 3 ),
			'length'     => array( 16, 19 ),
			'idRange'    => array( 9704 ),
		);
	}

	/** @return array<string,mixed> */
	private function gpnCardSchema(): array {
		return array(
			'name'       => 'Gerbang Pembayaran Nasional',
			'alias'      => 'gpn',
			'type'       => 'Debit Card',
			'breakpoint' => array( 4, 8, 12 ),
			'code'       => array( 'CVC', 3 ),
			'length'     => array( 16, 18, 19 ),
			'idRange'    => array( 1946, 50, 56, 58, array( 60, 63 ) ),
		);
	}

	/** @return array<string,mixed> */
	private function humoCardSchema(): array {
		return array(
			'name'       => 'Humo',
			'alias'      => 'humo',
			'breakpoint' => array( 4, 8, 12 ),
			'code'       => array( 'CVv', 3 ),
			'length'     => array( 16 ),
			'idRange'    => array( 9860 ),
		);
	}
};
