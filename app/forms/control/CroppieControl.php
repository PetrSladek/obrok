<?php

namespace App\Forms\Controls;

use App\Forms\Form;
use Croppie;
use Nette\Forms\Container;
use Nette\Http\FileUpload;
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

	/** @var string */
	private $emptyUrl;

	/** @var int[]  */
	private $boundarySize = [300, 300];

	/** @var int[]  */
	private $viewportSize = [250, 250];

	/** @var bool  */
	private $enableZoom = true;

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
		if ($value !== null && !($value instanceof Croppie || (is_array($value) && count($value) == 4)))
		{
			throw new \InvalidArgumentException("Value must be instance of Croppie or array of points [x, y, w, h]");
		}

		if(is_array($value))
		{
			list($x, $y, $h, $w) = $value;
			$value = new Croppie($x, $y, $x + $w, $y + $h);
		}

		return parent::setValue($value);
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

		if (!$upload && !$this->imageUrl)
		{
			$this->setValue(null);
		}
		else
		{
			$this->setValue(new Croppie($x1, $y1, $x2, $y2, $upload));
		}
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
	 *
	 * @return $this
	 */
	public function setImageUrl($imageUrl)
	{
		$this->imageUrl = $imageUrl;
		return $this;
	}


	/**
	 * Nastaví URL obrázku, který se zobrazí jako prázdný
	 *
	 * @param $emptyImageUrl
	 *
	 * @return $this
	 */
	public function setEmptyImageUrl($emptyImageUrl)
	{
		$this->emptyUrl = $emptyImageUrl;
		return $this;
	}

	/**
	 * Nastaví velikost viewportu
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function setViewportSize($width, $height)
	{
		$this->viewportSize = [$width, $height];
	}


	/**
	 * Nastaví velikost ohraničení
	 *
	 * @param int $width
	 * @param int $height
	 */
	public function setBoundarySize($width, $height)
	{
		$this->boundarySize = [$width, $height];
	}


	/**
	 * Vypne možnost zvětšení
	 */
	public function disableZoom()
	{
		$this->enableZoom = false;
	}


	/**
	 * Zaplne možnost zvětšení
	 */
	public function enableZoom()
	{
		$this->enableZoom = true;
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
			'data-image-url' => $this->imageUrl ?: $this->emptyUrl,
			'data-empty-url' => $this->emptyUrl,
			'data-options' => json_encode([
				'viewport' => [
					'width' => $this->viewportSize[0],
					'height' => $this->viewportSize[1],
				],
				'boundary' => [
					'width' => $this->boundarySize[0],
					'height' => $this->boundarySize[1],
				],
				'enableZoom' => $this->enableZoom,
			]),
			'style' => [
				'width' => $this->boundarySize[0] . 'px',
			],
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