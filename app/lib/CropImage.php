<?php

namespace Myann;

use Nette\Http\FileUpload;

//use Nette\Environment;

class CropImage
{
	/**
	 * @var string
	 */
	public $filename;

	/**
	 * @var int
	 */
	public $x;

	/**
	 * @var int
	 */
	public $y;

	/**
	 * @var int
	 */
	public $w;

	/**
	 * @var int
	 */
	public $h;

	/**
	 * @var string
	 */
	public $sess_key;

	/**
	 * @var FileUpload|null
	 */
	private $upload = null;


	/**
	 * CropImage constructor.
	 *
	 * @param null $value
	 */
	public function __construct($value = null)
	{
		foreach (array('filename', 'sess_key', 'x', 'y', 'w', 'h') as $key)
		{
			if (isset($value[$key]) && is_scalar($value[$key]))
			{
				$this->$key = $value[$key];
			}
			else
			{
				$this->$key = null;
			}
		}
	}


	/**
	 * @return string
	 */
	public function getFilename()
	{
		return $this->filename;
	}


	/**
	 * @param $filename
	 *
	 * @return $this
	 */
	public function setFilename($filename)
	{
		$this->filename = $filename;

		return $this;
	}


	/**
	 * @return array
	 */
	public function getCrop()
	{
		$result = array();
		foreach (array('x', 'y', 'w', 'h') as $key)
		{
			$result[$key] = $this->{$key};
		}

		return $result;
	}


	/**
	 * @param $crop
	 *
	 * @return $this
	 */
	public function setCrop($crop)
	{
		foreach (array('x', 'y', 'w', 'h') as $key)
		{
			if (isset($crop[$key]) && is_scalar($crop[$key]))
			{
				$this->$key = $crop[$key];
			}
		}

		return $this;
	}


	/**
	 * @return bool
	 */
	public function hasUploadedFile()
	{
		return is_object($this->upload)
			   && $this->upload instanceof FileUpload
			   && $this->upload->isOk()
			   && $this->upload->isImage();
	}


	/**
	 * @param FileUpload|null $file
	 *
	 * @return $this
	 */
	public function setUploadedFile(FileUpload $file = null)
	{
		if ($file === null)
		{
			$this->sess_key = null;
		}
		$this->upload = $file;

		return $this;
	}


	/**
	 * @return FileUpload|null
	 */
	public function getUploadedFile()
	{
		return $this->upload;
	}


	/**
	 * @param $control
	 *
	 * @return bool
	 */
	public static function isFilled($control)
	{
		$val = $control->getValue();

		return !empty($val->filename);
	}

}
