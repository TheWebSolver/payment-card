<?php
/**
 * The Payment Cards from a Class.
 *
 * @package TheWebSolver/Codegarage/Test
 */

class CardList {
	/** @return array<mixed[]> */
	public function __invoke() {
		return array(
			array(
				'name'       => 'Napas',
				'alias'      => 'napas',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVC', 3 ),
				'length'     => array( 16, 19 ),
				'idRange'    => array( 9704 ),
			),
			array(
				'name'       => 'Gerbang Pembayaran Nasional',
				'alias'      => 'gpn',
				'type'       => 'Debit Card',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVC', 3 ),
				'length'     => array( 16, 18, 19 ),
				'idRange'    => array( 1946, 50, 56, 58, array( 60, 63 ) ),
			),
			array(
				'name'       => 'Humo',
				'alias'      => 'humo',
				'breakpoint' => array( 4, 8, 12 ),
				'code'       => array( 'CVv', 3 ),
				'length'     => array( 16 ),
				'idRange'    => array( 9860 ),
			),
		);
	}
};

/*
------------------------------------
Alternatively, with anonymous class:
------------------------------------

return new class {
	public function __invoke(): array {
		return array(
			// ... Cards data.
		);
	}
}
*/
return new CardList();
