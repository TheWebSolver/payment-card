<?php
/**
 * The Payment Cards as array.
 *
 * @package TheWebSolver/Codegarage/Test
 */

use TheWebSolver\Codegarage\Test\Resource\NapasCard;

return array(
	array(
		'name'       => 'Napas',
		'alias'      => 'napas',
		'classname'  => NapasCard::class,
		'breakpoint' => array( 4, 8, 12 ),
		'code'       => array( 'CVC', 3 ),
		'length'     => array( 16, 19 ),
		'idRange'    => array( 9704 ),
	),
);
