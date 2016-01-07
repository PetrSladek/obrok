<?php

namespace App\Services;

use Nette\Http\FileUpload;
use Nette\InvalidArgumentException;
use Nette\Object;
use Nette\Utils\Image;
use Tracy\Debugger;

/**
 * Class ImageService
 * @package App\Services
 * @author  psl <petr.sladek@webnode.com>
 */
class ImageService extends Object
{
	/**
	 * Kvalita miniatur
	 *
	 * @var int
	 */
	public $quality = 100;

	/**
	 * Adresář pro upload originálů
	 *
	 * @var string
	 */
	protected $uploadDir;

	/**
	 * Adresář s veřejně dostupnou keší pro změnšené/ořazené obrázky
	 *
	 * @var string
	 */
	protected $cacheDir;

	/**
	 * Url adresa sahající na $cacheDir
	 *
	 * @var string
	 */
	protected $cacheUrl;


	/**
	 * ImageService constructor.
	 *
	 * @param string $uploadDir
	 * @param string $cacheDir
	 * @param string $cacheUrl
	 *
	 * @throws ImageServiceException
	 */
	public function __construct($uploadDir, $cacheDir, $cacheUrl)
	{
		$this->uploadDir = $uploadDir;
		$this->cacheDir = $cacheDir;
		$this->cacheUrl = $cacheUrl;

		if (!is_dir($this->getUploadDir()))
		{
			throw new ImageServiceException("Path {$this->uploadDir} must be directory!");
		}
		if (!is_writable($this->getUploadDir()))
		{
			throw new ImageServiceException("Directory {$this->uploadDir} must be writable!");
		}
		if (!is_dir($this->getCacheDir()))
		{
			throw new ImageServiceException("Path {$this->cacheDir} must be directory!");
		}
		if (!is_writable($this->getCacheDir()))
		{
			throw new ImageServiceException("Directory {$this->cacheDir} must be writable!");
		}

	}


	/**
	 * Vygeneruje miniaturu (vezme z keše) a vrátí na ni URLs
	 *
	 * @param      $filename
	 * @param null $width
	 * @param null $height
	 * @param int  $flags
	 * @param null $crop
	 *
	 * @return string
	 * @throws ImageServiceException
	 */
	public function getImageUrl($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{

		$cacheFilename = $this->getCacheFilename($filename, $width, $height, $flags, $crop);
		if (!is_readable($this->getCacheDir() . $cacheFilename))
		{
			$this->processImage($filename, $width, $height, $flags, $crop);
		}

		return $this->getCacheUrl() . $cacheFilename;
	}


	/**
	 * Vytvoří miniaturu a vrátí objekt obrázku
	 *
	 * @param      $filename
	 * @param null $width
	 * @param null $height
	 * @param int  $flags
	 * @param null $crop
	 *
	 * @return Image
	 * @throws ImageServiceException
	 */
	public function processImage($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{

		ini_set('memory_limit', 0);

		$fotoUpload = $this->getUploadDir() . $filename;

		if (!is_file($fotoUpload))
		{
			throw new ImageServiceException("Soubor $fotoUpload neexisuje");
		}

		try
		{
			$image = Image::fromFile($fotoUpload);

			// ořežeme originál
			if ($crop)
			{
				try
				{
					$image->crop((int) $crop['x'], (int) $crop['y'], (int) $crop['w'], (int) $crop['h']);
				}
				catch (\Exception $e)
				{
					Debugger::log($e);
				}
			}

			if ($width || $height)
			{
				$image->resize($width, $height, $flags);
			}

			//        if($watermark && $image->getWidth() > 300) {
			//            $watermark = Image::fromFile( $this->getUploadDir('watermark.png') );
			//            $watermark->resize(250, null);
			//            $image->place($watermark, 10, 5);
			//        }
			//        $image->sharpen();
			$image->save($this->getCacheDir() . $this->getCacheFilename($filename, $width, $height, $flags, $crop));
		}
		catch (\Exception $e)
		{
			Debugger::log($e);
		}

		return $image;
	}


	/**
	 * Vygeneruje miniaturu (vezme z keše) a vrátí objekt obrázku
	 *
	 * @param string $filename jmeno souboru v upload
	 *
	 * @return Image
	 */
	public function getImage($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{

		$fotoCached = $this->getCacheDir() . $this->getCacheFilename($filename, $width, $height, $flags, $crop);
		if (is_readable($fotoCached))
		{
			return Image::fromFile($fotoCached);
		}

		return $this->processImage($filename, $width, $height, $flags, $crop);
	}


	/**
	 * @return string
	 */
	public function getUploadDir()
	{
		return str_replace('//', '/', $this->uploadDir . "/"); // oddelame dvojity lomitka a pridame lomitko na konec
	}


	/**
	 * @return string
	 */
	public function getCacheDir()
	{
		return str_replace('//', '/', $this->cacheDir . "/"); // oddelame dvojity lomitka a pridame lomitko na konec
	}


	/**
	 * @return string
	 */
	public function getCacheUrl()
	{
		return str_replace('//', '/', $this->cacheUrl . "/");;
	}


	/**
	 * Vrátí název souboru pro keš
	 *
	 * @param      $filename
	 * @param      $width
	 * @param      $height
	 * @param      $flags
	 * @param null $crop
	 *
	 * @return mixed|string
	 */
	public function getCacheFilename($filename, $width, $height, $flags, $crop = null)
	{

		$fileInfo = pathinfo($this->getUploadDir() . $filename);

		$cachename = str_replace(".", "-", $fileInfo['basename']);
		$cachename .= "_{$width}x{$height}_{$flags}";
		if ($crop)
		{
			$cachename .= "_{$crop['x']}-{$crop['y']}-{$crop['w']}-{$crop['h']}";
		}
		$cachename .= "." . $fileInfo['extension'];

		return $cachename;
	}


	/**
	 * Vrátí typ originálního obrázku podle názvu
	 *
	 * @param $filename
	 *
	 * @return int
	 */
	public function getImageType($filename)
	{
		$file = $this->getUploadDir() . $filename;

		switch (strtolower(pathinfo($file, PATHINFO_EXTENSION)))
		{
			case 'jpg':
			case 'jpeg':
				$type = Image::JPEG;
				break;
			case 'png':
				$type = Image::PNG;
				break;
			case 'gif':
				$type = Image::GIF;
				break;
			default:
				throw new InvalidArgumentException("Unsupported image type.");
		}

		return $type;
	}


	/**
	 * Uloží uploadnutý obrázek
	 *
	 * @param FileUpload $file
	 * @param string     $dir
	 *
	 * @return null|string
	 */
	public function saveImage(FileUpload $file, $dir = 'images')
	{

		// berem jenom cajk obrázky
		if (!$file->isOk())
		{
			return null;
		}

		$filename = strtolower(time() . '_' . $file->getSanitizedName());

		$file->move($this->getUploadDir() . $dir . '/' . $filename);

		return $dir . '/' . $filename;
	}
}


/**
 * Class ImageServiceException
 * @package App\Services
 * @author  psl <petr.sladek@webnode.com>
 */
class ImageServiceException extends \Exception
{

}