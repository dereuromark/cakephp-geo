<?php

use Cake\Core\Configure;
use Cake\Core\Configure\Engine\PhpConfig;
use Cake\Log\Log;

Log::config('scope_test', [
	'engine' => Configure::read('App.namespace'),
	'types' => array('debug'),
	'scopes' => array('geocode'),
]);
