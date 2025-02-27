<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Resource\NapasCard;

return array(
	array(
		'name'       => 'Napas',
		'alias'      => 'napas',
		'classname'  => NapasCard::class,
		'breakpoint' => array( 4, 8, 12 ),
		'code'       => array( 'CVC', true ),
		'length'     => array( 16, 19 ),
		'idRange'    => array( 9704 ),
	),
);
