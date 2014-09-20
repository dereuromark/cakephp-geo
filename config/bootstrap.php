<?php

use Cake\Log\Log;

Log::config('scope_test', [
	'engine' => Configure::read('App.namespace'),
	'types' => array('debug'),
	'scopes' => array('geocode'),
]);
