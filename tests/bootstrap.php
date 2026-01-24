<?php

use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\TestSuite\Fixture\SchemaLoader;
use Cake\View\View;
use Geo\GeoPlugin;
use TestApp\Controller\AppController;

if (!defined('WINDOWS')) {
	if (DS == '\\' || substr(PHP_OS, 0, 3) === 'WIN') {
		define('WINDOWS', true);
	} else {
		define('WINDOWS', false);
	}
}

define('ROOT', dirname(__DIR__));
define('APP_DIR', 'src');

define('APP', rtrim(sys_get_temp_dir(), DS) . DS . APP_DIR . DS);
if (!is_dir(APP)) {
	mkdir(APP, 0770, true);
}

define('TMP', ROOT . DS . 'tmp' . DS);
if (!is_dir(TMP)) {
	mkdir(TMP, 0770, true);
}

define('CONFIG', dirname(__FILE__) . DS . 'config' . DS);
define('LOGS', TMP . 'logs' . DS);
define('CACHE', TMP . 'cache' . DS);

define('CAKE_CORE_INCLUDE_PATH', ROOT . '/vendor/cakephp/cakephp');
define('CORE_PATH', CAKE_CORE_INCLUDE_PATH . DS);
define('CAKE', CORE_PATH . 'src' . DS);

define('WWW_ROOT', APP . 'webroot' . DS);

require dirname(__DIR__) . '/vendor/autoload.php';
require CORE_PATH . 'config/bootstrap.php';
require CAKE_CORE_INCLUDE_PATH . '/src/functions.php';

Configure::write('App', [
	'encoding' => 'utf8',
	'namespace' => 'TestApp',
	'paths' => [
		'templates' => dirname(__FILE__) . DS . 'TestApp' . DS . 'templates' . DS,
	],
]);

Configure::write('debug', true);

$cache = [
	'default' => [
		'engine' => 'File',
		'path' => CACHE,
	],
	'_cake_translations_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_translations_',
		'path' => CACHE . 'persistent/',
		'serialize' => true,
		'duration' => '+10 seconds',
	],
	'_cake_model_' => [
		'className' => 'File',
		'prefix' => 'myapp_cake_model_',
		'path' => CACHE . 'models/',
		'serialize' => 'File',
		'duration' => '+10 seconds',
	],
];

Cache::setConfig($cache);

class_alias(AppController::class, 'App\Controller\AppController');
class_alias(View::class, 'App\View\AppView');

Plugin::getCollection()->add(new GeoPlugin());

if (file_exists(CONFIG . 'app_local.php')) {
	Configure::load('app_local', 'default');
}

// Ensure default test connection is defined
if (!getenv('DB_URL')) {
	putenv('DB_URL=sqlite:///:memory:');
}

ConnectionManager::setConfig('test', [
	'url' => getenv('DB_URL') ?: null,
	'timezone' => 'UTC',
	'quoteIdentifiers' => true,
	'cacheMetadata' => true,
]);

// Google maps API key required
Configure::write('Geocoder', [
	'apiKey' => env('API_KEY'), // local, set through `export API_KEY=".."` in CLI
]);

/**
 * @var \Cake\Database\Connection $db
 */
$db = ConnectionManager::get('test');
if ($db->getDriver() instanceof \Cake\Database\Driver\Postgres) {
	//$db->execute('CREATE EXTENSION postgis;')->fetchAll();
	//debug($db->execute('SELECT postgis_full_version();')->fetchAssoc());
}

if (env('FIXTURE_SCHEMA_METADATA')) {
	$loader = new SchemaLoader();
	$loader->loadInternalFile(env('FIXTURE_SCHEMA_METADATA'));
}

if ($db->getDriver() instanceof \Cake\Database\Driver\Mysql) {
	try {
		$db->execute('ALTER TABLE spatial_addresses ADD SPATIAL INDEX coordinates_spatial(coordinates);');
	} catch (\Cake\Database\Exception\QueryException) {
		// Index already exists
	}
}
