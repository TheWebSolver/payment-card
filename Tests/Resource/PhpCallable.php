<?php
/**
 * The Payment Cards from Closure.
 *
 * @package TheWebSolver/Codegarage/Test
 */

return function () {
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
			'name'       => 'Humo',
			'alias'      => 'humo',
			'breakpoint' => array( 4, 8, 12 ),
			'code'       => array( 'CVv', 3 ),
			'length'     => array( 16 ),
			'idRange'    => array( 9860 ),
		),
	);
};
