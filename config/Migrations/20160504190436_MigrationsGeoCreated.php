<?php

use Migrations\BaseMigration;

// phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
// phpcs:disable PSR2R.Classes.ClassFileName.NoMatch
class MigrationsGeoCreated extends BaseMigration {

	/**
	 * @inheritDoc
	 */
	public function change() {
		$this->table('geocoded_addresses')
			->addColumn('created', 'datetime', [
				'default' => null,
				'null' => false,
			])
			->update();
	}

}
