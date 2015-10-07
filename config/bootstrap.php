<?php

use Cake\Log\Log;

Log::config('geo', [
	'className' => 'Cake\Log\Engine\FileLog',
	'path' => LOGS,
	'levels' => ['debug'],
	'scopes' => ['geocode'],
	'file' => 'geocode',
]);
