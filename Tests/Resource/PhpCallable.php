<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Fixture\CreditCard;

return function () {
	return [
		[
			'name'       => 'Napas',
			'alias'      => 'napas',
			'classname'  => CreditCard::class,
			'code'       => [
				'name' => 'CVC',
				'size' => 3,
			],
			'breakpoint' => [ 4, 8, 12 ],
			'length'     => [ 16, 19 ],
			'idRange'    => [ 9704 ],
		],
		[
			'name'       => 'Humo',
			'alias'      => 'humo',
			'code'       => [
				'name' => 'CVv',
				'size' => 3,
			],
			'breakpoint' => [ 4, 8, 12 ],
			'length'     => [ 16 ],
			'idRange'    => [ 9860 ],
		],
	];
};
