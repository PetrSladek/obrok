<?php

if (@!include __DIR__ . '/../vendor/autoload.php') {
    echo 'Install Nette Tester using `composer update --dev`';
    exit(1);
}

// configure environment
Tester\Environment::setup();
class_alias('Tester\Assert', 'Assert');
date_default_timezone_set('Europe/Prague');

// create temporary directory
@mkdir( __DIR__ . '/../temp/');
define('TEMP_DIR', __DIR__ . '/../temp/' . (isset($_SERVER['argv']) ? md5(serialize($_SERVER['argv'])) : getmypid()));
Tester\Helpers::purge(TEMP_DIR);


$_SERVER = array_intersect_key($_SERVER, array_flip(array(
    'PHP_SELF', 'SCRIPT_NAME', 'SERVER_ADDR', 'SERVER_SOFTWARE', 'HTTP_HOST', 'DOCUMENT_ROOT', 'OS', 'argc', 'argv')));
$_SERVER['REQUEST_TIME'] = 1234567890;
$_ENV = $_GET = $_POST = array();

function id($val) {
    return $val;
}

function run(Tester\TestCase $testCase) {
    $testCase->run(isset($_SERVER['argv'][1]) ? $_SERVER['argv'][1] : NULL);
}


$configurator = new Nette\Configurator;
$configurator->setDebugMode(false);
$configurator->setTempDirectory(TEMP_DIR);
$configurator->createRobotLoader()
    ->addDirectory(__DIR__ . '/../app')
    ->register();
$configurator->addParameters([
    'appDir' => __DIR__ . '/../app',
    'wwwDir' => __DIR__ . '/../www',
]);

$configurator->addConfig(__DIR__ . '/../app/config/config.neon');
$configurator->addConfig(__DIR__ . '/../app/config/config.local.neon');
$configurator->addConfig(__DIR__ . '/config.local.neon');

//var_dump($configurator);
//php_ini_loaded_file();


return $configurator->createContainer();