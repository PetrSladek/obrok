<?php


namespace PetrSladek\SkautIS\DI;

use Nette\DI\CompilerExtension;
use Nette;


class SkautISExtension extends CompilerExtension
{

	/** @var array */
	public $defaults = array(
//		'appId' => NULL, // kdyby-style naming
//		'testMode' => true,
        'clearAllWithLogout' => true,
	);



	public function loadConfiguration()
	{
		$builder = $this->getContainerBuilder();
		$config = $this->getConfig($this->defaults);


		$builder->addDefinition($this->prefix('skautis'))
			->setClass('\PetrSladek\SkautIS\SkautIS');


		$builder->addDefinition($this->prefix('session'))
			->setClass('PetrSladek\SkautIS\SessionStorage');


		if ($config['clearAllWithLogout']) {
			$builder->getDefinition('user')
				->addSetup('$sl = ?; ?->onLoggedOut[] = function () use ($sl) { $sl->getService(?)->clearAll(); }', array(
					'@container', '@self', $this->prefix('session')
				));
		}
	}




	/**
	 * @param \Nette\Configurator $configurator
	 */
	public static function register(Nette\Configurator $configurator)
	{
		$configurator->onCompile[] = function ($config, Nette\DI\Compiler $compiler) {
			$compiler->addExtension('skautis', new SkautISExtension());
		};
	}

}
