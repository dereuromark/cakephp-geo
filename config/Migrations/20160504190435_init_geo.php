<?php

use Phinx\Migration\AbstractMigration;

class InitGeo extends AbstractMigration {
	/**
	 * @inheritDoc
	 */
	public function change() {
		$sql = <<<SQL

CREATE TABLE IF NOT EXISTS `geocoded_addresses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) COLLATE utf8_bin NOT NULL,
  `formatted_address` varchar(255) COLLATE utf8_bin DEFAULT NULL,
  `country` varchar(3) COLLATE utf8_bin DEFAULT NULL,
  `lat` decimal(10,7) DEFAULT NULL,
  `lng` decimal(10,7) DEFAULT NULL,
  `data` text COLLATE utf8_bin DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `address` (`address`)
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_bin;

SQL;
		$this->query($sql);
	}

}
