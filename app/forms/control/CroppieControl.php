<?php

namespace App\Forms\Controls;

use App\Forms\Form;
use Croppie;
use Nette\Forms\Container;
use Nette\Utils\Html;

/**
 * Class CroppieControl
 *
 * @author  psl <petr.sladek@webnode.com>
 */
class CroppieControl extends \Nette\Forms\Controls\BaseControl
{

	/** @var Croppie|null current control value */
	protected $value;

	/** @var string */
	private $imageUrl;


	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 *
	 * @param  \Nette\ComponentModel\IComponent
	 * @return void
	 */
	protected function attached($form)
	{
		if ($form instanceof \Nette\Forms\Form) {
			if ($form->getMethod() !== \Nette\Forms\Form::POST) {
				throw new \Nette\InvalidStateException('File upload requires method POST.');
			}
			$form->getElementPrototype()->enctype = 'multipart/form-data';
		}
		parent::attached($form);
	}


	/**
	 * @inheritdoc
	 */
	public function setValue($value)
	{
		if ($value !== null && !($value instanceof Croppie))
		{
			throw new \InvalidArgumentException("Value must be instance of Croppie");
		}

		return parent::setValue($value);
	}


	/**
	 * @inheritdoc
	 */
	public function setDefaultValue($value)
	{
		if ($value !== null && !($value instanceof Croppie || is_array($value)))
		{
			throw new \InvalidArgumentException("Value must be instance of Croppie");
		}

		if(is_array($value))
		{
			list($x, $y, $h, $w) = $value;
			$value = new Croppie($x, $y, $x + $w, $y + $h);
		}

		parent::setDefaultValue($value);
	}


	/**
	 * @inheritdoc
	 */
	public function loadHttpData()
	{
		$x1 = $this->getHttpData(Form::DATA_TEXT, '[x1]');
		$y1 = $this->getHttpData(Form::DATA_TEXT, '[y1]');
		$x2 = $this->getHttpData(Form::DATA_TEXT, '[x2]');
		$y2 = $this->getHttpData(Form::DATA_TEXT, '[y2]');
		$upload = $this->getHttpData(Form::DATA_FILE, '[upload]');

		$this->setValue(new Croppie($x1, $y1, $x2, $y2, $upload));
	}

	/**
	 * @inheritdoc
	 */
	public function getControl()
	{
		$this->setOption('rendered', true);

		$upload = Html::el('input', [
			'name' => $this->getPartHtmlName('upload'),
			'type' => 'file',
			'accept' => "image/*",
		]);

		$x1 = Html::el('input', [
			'name'  => $this->getPartHtmlName('x1'),
			'type'  => 'hidden',
			'value' => $this->value && $this->value->hasPoints() ? $this->value->getPoints()[0] : 0,
		]);

		$y1 = Html::el('input', [
			'name'  => $this->getPartHtmlName('y1'),
			'type'  => 'hidden',
			'value' => $this->value && $this->value->hasPoints() ? $this->value->getPoints()[1] : 0,
		]);

		$x2 = Html::el('input', [
			'name'  => $this->getPartHtmlName('x2'),
			'type'  => 'hidden',
			'value' => $this->value && $this->value->hasPoints() ? $this->value->getPoints()[2] : 0,
		]);

		$y2 = Html::el('input', [
			'name'  => $this->getPartHtmlName('y2'),
			'type'  => 'hidden',
			'value' => $this->value && $this->value->hasPoints() ? $this->value->getPoints()[3] : 0,
		]);

		$croppie = Html::el('div', [
			'data-croppie' => true,
		]);


		$control = Html::el('div', [
			'class' => 'croppie-control',
			'data-image-url' => $this->imageUrl,
		]);

		$control->add($croppie)
				->add($upload)
				->add($x1)
				->add($y1)
				->add($x2)
				->add($y2);

		return $control;

	}


	/**
	 * @param $part
	 *
	 * @return string
	 */
	private function getPartHtmlName($part)
	{
		return $this->getHtmlName() . '[' . $part . ']';
	}


	/**
	 * Nastaví URL obrázku
	 *
	 * @param $imageUrl
	 */
	public function setImageUrl($imageUrl)
	{
		$this->imageUrl = $imageUrl;
	}


	/**
	 * Registers method 'addCroppie' adding CroppieControl to form
	 *
	 * @param string $method
	 */
	public static function register($method = 'addCroppie')
	{
		Container::extensionMethod($method, function ($container, $name, $caption = null) {
			return $container[$name] = new CroppieControl($caption);
		});
	}


}