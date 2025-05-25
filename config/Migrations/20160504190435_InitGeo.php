<?php

use Migrations\BaseMigration;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR2R.Classes.ClassFileName.NoMatch
class InitGeo extends BaseMigration {

	/**
	 * @inheritDoc
	 */
	public function up() {
		$this->table('geocoded_addresses')
			->addColumn('address', 'string', [
			  'default' => null,
			  'limit' => 255,
			  'null' => false,
			])
			->addColumn('formatted_address', 'string', [
			  'default' => null,
			  'limit' => 255,
			  'null' => true,
			])
			->addColumn('country', 'string', [
			  'default' => null,
			  'limit' => 3,
			  'null' => true,
			])
			->addColumn('lat', 'float', [
			  'default' => null,
			  'null' => true,
			])
			->addColumn('lng', 'float', [
			  'default' => null,
			  'null' => true,
			])
			->addColumn('data', 'text', [
			  'default' => null,
			  'limit' => null,
			  'null' => true,
			])
			->addIndex(
				[
				  'address',
				],
				['unique' => true],
			)
			->create();
	}

	/**
	   * @inheritDoc
	   */
	public function down() {
		$this->table('geocoded_addresses')->drop()->save();
	}

}
