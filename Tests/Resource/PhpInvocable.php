<?php
declare( strict_types = 1 );

use TheWebSolver\Codegarage\Test\Fixture\CreditCard;

return new class() {
	/** @return array<string,mixed[]> */
	public function __invoke() {
		return [
			'napas' => $this->napasCardSchema(),
			'gpn'   => $this->gpnCardSchema(),
			'humo'  => $this->humoCardSchema(),
		];
	}

	/** @return array<string,mixed> */
	private function napasCardSchema(): array {
		return [
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
		];
	}

	/** @return array<string,mixed> */
	private function gpnCardSchema(): array {
		return [
			'name'       => 'Gerbang Pembayaran Nasional',
			'alias'      => 'gpn',
			'type'       => 'Debit Card',
			'code'       => [
				'name' => 'CVC',
				'size' => 3,
			],
			'breakpoint' => [ 4, 8, 12 ],
			'length'     => [ 16, 18, 19 ],
			'idRange'    => [ 1946, 50, 56, 58, [ 60, 63 ] ],
		];
	}

	/** @return array<string,mixed> */
	private function humoCardSchema(): array {
		return [
			'name'       => 'Humo',
			'alias'      => 'humo',
			'code'       => [
				'name' => 'CVv',
				'size' => 3,
			],
			'breakpoint' => [ 4, 8, 12 ],
			'length'     => [ 16 ],
			'idRange'    => [ 9860 ],
		];
	}
};
