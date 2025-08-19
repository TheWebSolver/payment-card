<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Resource\NapasCard;

return [
	[
		'name'       => 'Napas',
		'alias'      => 'napas',
		'classname'  => NapasCard::class,
		'breakpoint' => [ 4, 8, 12 ],
		'code'       => [ 'CVC', true ],
		'length'     => [ 16, 19 ],
		'idRange'    => [ 9704 ],
	],
];
