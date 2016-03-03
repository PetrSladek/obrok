<?php


class Croppie
{

	/**
	 * @var int
	 */
	private $x1;

	/**
	 * @var int
	 */
	private $y1;

	/**
	 * @var int
	 */
	private $x2;

	/**
	 * @var int
	 */
	private $y2;

	/**
	 * @var \Nette\Http\FileUpload|null
	 */
	private $upload;


	/**
	 * Croppie constructor.
	 *
	 * @param int                         $x1
	 * @param int                         $y1
	 * @param int                         $x2
	 * @param int                         $y2
	 * @param \Nette\Http\FileUpload|null $upload
	 */
	public function __construct($x1, $y1, $x2, $y2, \Nette\Http\FileUpload $upload = null)
	{
		$this->x1 = (int) $x1;
		$this->y1 = (int) $y1;
		$this->x2 = (int) $x2;
		$this->y2 = (int) $y2;
		$this->upload = $upload;
	}


	/**
	 * @return bool
	 */
	public function hasPoints()
	{
		return $this->x2 > $this->x1 && $this->y2 > $this->y1;
	}


	/**
	 * @return int[]
	 */
	public function getPoints()
	{
		return [$this->x1, $this->y1, $this->x2, $this->y2];
	}


	/**
	 * @return int[]
	 */
	public function getCrop()
	{
		return [$this->x1, $this->y1, $this->x2 - $this->x1, $this->y2 - $this->y1];
	}


	/**
	 * @return bool
	 */
	public function hasFileUpload()
	{
		return $this->upload && $this->upload->isImage();
	}


	/**
	 * @return \Nette\Http\FileUpload|null
	 */
	public function getFileUpload()
	{
		return $this->upload;
	}


	/**
	 * @return \Nette\Utils\Image|null
	 */
	public function getUploadedImage()
	{
		return $this->hasFileUpload() ? $this->getFileUpload()->toImage() : null;
	}


	/**
	 * @return \Nette\Utils\Image|null
	 */
	public function getCroppedUploadedImage()
	{
		list($x, $y, $w, $h) = $this->getCrop();

		return $this->getUploadedImage() ? $this->getUploadedImage()->crop($x, $y, $w, $h) : null;
	}

}