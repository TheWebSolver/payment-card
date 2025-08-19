<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Resource\NapasCard;

return function () {
	return [
		[
			'name'       => 'Napas',
			'alias'      => 'napas',
			'classname'  => NapasCard::class,
			'breakpoint' => [ 4, 8, 12 ],
			'code'       => [ 'CVC', 3 ],
			'length'     => [ 16, 19 ],
			'idRange'    => [ 9704 ],
		],
		[
			'name'       => 'Humo',
			'alias'      => 'humo',
			'breakpoint' => [ 4, 8, 12 ],
			'code'       => [ 'CVv', 3 ],
			'length'     => [ 16 ],
			'idRange'    => [ 9860 ],
		],
	];
};
