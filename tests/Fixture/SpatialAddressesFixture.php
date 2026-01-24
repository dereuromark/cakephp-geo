<?php

namespace Geo\Test\Fixture;

use Cake\TestSuite\Fixture\TestFixture;

class SpatialAddressesFixture extends TestFixture {

	/**
	 * Fields
	 *
	 * @var array
	 */
	public array $fields = [
		'id' => ['type' => 'integer'],
		'address' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => 190, 'comment' => 'street address and street numbe'],
		'lat' => ['type' => 'float', 'null' => false, 'default' => null, 'comment' => 'maps.google.de latitude'],
		'lng' => ['type' => 'float', 'null' => false, 'default' => null, 'comment' => 'maps.google.de longitude'],
		'coordinates' => ['type' => 'point', 'null' => false],
		'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'modified' => ['type' => 'datetime', 'null' => true, 'default' => null],
		'_constraints' => [
			'primary' => ['type' => 'primary', 'columns' => ['id']],
		],
		'_indexes' => [
			//'coordinates_spatial' => ['type' => 'spatial', 'columns' => ['coordinates'], 'length' => []],
		],
	];

	/**
	 * Records
	 *
	 * @var array
	 */
	public array $records = [];

}
