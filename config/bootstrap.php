<?php

use Cake\Log\Log;

Log::config('geo', [
	'engine' => 'File',
	'types' => ['debug'],
	'scopes' => ['geocode'],
]);
