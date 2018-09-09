<?php

require __DIR__ . '/vendor/autoload.php';

$dotenv = new Dotenv\Dotenv(__DIR__);
$dotenv->load();

return [
	'lebeda' => [
		'remote' => 'ftp://is-obrok19.skauting.cz/',
		'user' => getenv('FTP_USER'),
		'password' => getenv('FTP_PASS'),

		'local' => '.',
		'test' => false,

		'ignore' => "
		    .git*
		    .idea
			app/config/config.local.neon
			app/config/config.local.*.neon
			log/*
			temp/*
			www/storage/webimages/
			deployment.log
		",

		'allowdelete' => 'yes',

		'purge' => ['temp/cache'],
	],

	'tempDir' => __DIR__ . '/temp',
	'colors' => false,
];