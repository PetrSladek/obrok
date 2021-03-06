<?php

require __DIR__ . '/../vendor/autoload.php';

$configurator = new Nette\Configurator;
$configurator->setDebugMode(['46.149.121.35', '193.86.73.180']);
$configurator->enableDebugger(__DIR__ . '/../log');
$configurator->setTempDirectory(__DIR__ . '/../temp');

$configurator->createRobotLoader()
	->addDirectory(__DIR__)
	->register();

$configurator->addConfig(__DIR__ . '/config/config.neon');
$configurator->addConfig(__DIR__ . '/config/config.local.neon');

$container = $configurator->createContainer();


\Nette\Forms\Container::extensionMethod('addDatePicker', function(\Nette\Forms\Container $container, $name, $label = NULL) {
    return $container[$name] = new \Nextras\Forms\Controls\DatePicker($label);
});
\Nette\Forms\Container::extensionMethod('addDateTimePicker', function(\Nette\Forms\Container $container, $name, $label = NULL) {
    return $container[$name] = new \Nextras\Forms\Controls\DateTimePicker($label);
});
\Nette\Forms\Container::extensionMethod('addTypeahead', function(\Nette\Forms\Container $container, $name, $label = NULL, $callback = NULL) {
    return $container[$name] = new \Nextras\Forms\Controls\Typeahead($label, $callback);
});



return $container;
