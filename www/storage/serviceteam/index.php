<?php
//die('Bude spusteno 1.12.2012 na MIXEMu');
// absolute filesystem path to this web root
define('WWW_DIR', dirname(__FILE__).'/../_production/');

// absolute filesystem path to the application root
define('APP_DIR', WWW_DIR . '/app');

// absolute filesystem path to the libraries
define('LIBS_DIR', WWW_DIR . '/libs');

// uncomment this line if you must temporarily take down your site for maintenance
// require APP_DIR . '/templates/maintenance.phtml';

// load bootstrap file
require APP_DIR . '/bootstrap.php';
