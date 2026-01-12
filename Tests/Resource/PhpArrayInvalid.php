<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Fixture\NapasCard;

return [
	[
		'name'       => 'Napas',
		'alias'      => 'napas',
		'classname'  => NapasCard::class,
		'code'       => [
			'name' => 'CVC',
			'size' => true,
		],
		'breakpoint' => [ 4, 8, 12 ],
		'length'     => [ 16, 19 ],
		'idRange'    => [ 9704 ],
	],
];
