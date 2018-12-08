<?php

namespace App\Forms;

use App\Forms\Controls\CroppieControl;
use Nette;
use Nette\Forms\Controls;

use VojtechDobes\NetteForms\GpsPicker;
use VojtechDobes\NetteForms\GpsPositionPicker;

/**
 * Class Form
 * @package App\Forms
 *
 * @method \Nextras\Forms\Controls\Typeahead addTypeahead()
 */
class Form extends Nette\Application\UI\Form
{

	/**
	 * Form constructor.
	 *
	 * @param Nette\ComponentModel\IContainer|null $parent
	 * @param null                                 $name
	 */
	public function __construct(Nette\ComponentModel\IContainer $parent = null, $name = null)
	{
		parent::__construct($parent = null, $name = null);

		// setup form rendering
		$renderer = $this->getRenderer();
		$renderer->wrappers['controls']['container'] = null;
		$renderer->wrappers['pair']['container'] = 'div class=form-group';
		$renderer->wrappers['pair']['.error'] = 'has-error';
		$renderer->wrappers['control']['container'] = 'div class=col-sm-8';
		$renderer->wrappers['label']['container'] = 'div class="col-sm-4 control-label"';
		$renderer->wrappers['control']['description'] = 'span class=help-block';
		$renderer->wrappers['control']['errorcontainer'] = 'span class=help-block';
		$renderer->wrappers['error']['container'] = 'div class="alert alert-danger"';
		$renderer->wrappers['error']['item'] = 'p';

		// make form and controls compatible with Twitter Bootstrap
		$this->getElementPrototype()->class('form-horizontal');
	}


	/**
	 * Nastavit form jako ajxový/neajaxový
	 * @param bool $ajax
	 */
	public function setAjax($ajax = true)
	{
		$this->getElementPrototype()->class('form-horizontal' . ($ajax ? ' ajax' : null));
	}


	/**
	 * @inheritdoc
	 */
	public function render(...$args)
	{
		foreach ($this->getControls() as $control)
		{
			if ($control instanceof Controls\Button)
			{
				$control->getControlPrototype()->addClass(empty($usedPrimary) ? 'btn btn-primary' : 'btn btn-default');
				$usedPrimary = true;
			}
			elseif ($control instanceof Controls\TextBase || $control instanceof Controls\SelectBox || $control instanceof Controls\MultiSelectBox)
			{
				$control->getControlPrototype()->addClass('form-control');

			}
			elseif ($control instanceof Controls\Checkbox || $control instanceof Controls\CheckboxList || $control instanceof Controls\RadioList)
			{
				$control->getSeparatorPrototype()->setName('div')->addClass($control->getControlPrototype()->type);
			}
		}

		parent::render(...$args);
	}


	/**
	 * Přidá GPS picker
	 *
	 * @param       $name
	 * @param null  $caption
	 * @param array $options
	 *
	 * @return GpsPositionPicker
	 */
	public function addGpsPicker($name, $caption = null, $options = array())
	{
		$driver = GpsPicker::DRIVER_GOOGLE;
		$type = GpsPicker::TYPE_ROADMAP;

		if (!isset($options['driver']))
		{
			$options['driver'] = $driver;
		}
		if (!isset($options['type']))
		{
			$options['type'] = $type;
		}

		return $this[$name] = new GpsPositionPicker($caption, $options);
	}



	/**
	 * Přidá GPS picker
	 *
	 * @param       $name
	 * @param null  $caption
	 * @param array $options
	 *
	 * @return CroppieControl
	 */
	public function addCroppie($name, $caption = null)
	{
		return $this[$name] = new CroppieControl($caption);
	}


	/**
	 * Check for phone number validity
	 *
	 * @param string $phoneNumber Phone number to validate
	 *
	 * @return boolean Validity is ok or not
	 */
	static public function isPhoneNumber(Nette\Forms\IControl $item)
	{
		$phoneNumber = $item->getValue();

		return preg_match('/^[+0-9. ()-]*$/ui', $phoneNumber);
	}

}