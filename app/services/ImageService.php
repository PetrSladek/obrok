<?php

namespace App\Services;

use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\Image;
use Tracy\Debugger;


/**
 * Class ImageService
 * @package App\Services
 * @author  psl <petr.sladek@webnode.com>
 */
class ImageService
{

	use SmartObject;

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
	 * @var callable[]
	 */
	public $onBeforeSaveThumbnail;


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
	 * @param string     $filename
	 * @param int|null   $width
	 * @param int|null   $height
	 * @param int|string $flags
	 * @param int[]|null $crop ořez obrázku x,y,w,h
	 *
	 * @return string
	 */
	public function getImageUrl($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{
		$flags = $this->convertFlags($flags);

		try
		{
			$cacheFilename = $this->getCacheFilename($filename, $width, $height, $flags, $crop);
			if (!file_exists($this->getCacheDir() . $cacheFilename))
			{
				$this->processImage($filename, $width, $height, $flags, $crop);
			}
		}
		catch (\Exception $e)
		{
			Debugger::log($e);
			return null;
		}

		return $this->getCacheUrl() . $cacheFilename;
	}


	/**
	 * Vygeneruje miniaturu (vezme z keše) a vrátí objekt obrázku
	 *
	 * @param string     $filename
	 * @param int|null   $width
	 * @param int|null   $height
	 * @param int|string $flags
	 * @param int[]|null $crop ořez obrázku x,y,w,h
	 *
	 * @return Image
	 */
	public function getImage($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{
		$flags = $this->convertFlags($flags);
		$cached = $this->getCacheDir() . $this->getCacheFilename($filename, $width, $height, $flags, $crop);
		if (is_readable($cached))
		{
			return Image::fromFile($cached);
		}

		return $this->processImage($filename, $width, $height, $flags, $crop);
	}


	/**
	 * @param $flags
	 *
	 * @return int
	 */
	private function convertFlags($flags)
	{
		if (is_string($flags))
		{
			switch (strtolower($flags))
			{
				case 'fit':
					return Image::FIT;
				case 'exact':
					return Image::EXACT;
				case 'fill':
					return Image::FILL;
			}
		}

		return $flags;
	}


	/**
	 * Vytvoří miniaturu a vrátí objekt obrázku
	 *
	 * @param string     $filename
	 * @param int|null   $width
	 * @param int|null   $height
	 * @param int        $flags
	 * @param int[]|null $crop
	 *
	 * @return Image
	 * @throws ImageServiceException
	 */
	public function processImage($filename, $width = null, $height = null, $flags = Image::FIT, $crop = null)
	{
		$fotoUpload = $this->getUploadDir() . $filename;

		if (!is_file($fotoUpload))
		{
			throw new ImageServiceException("Soubor $fotoUpload neexisuje");
		}

		$image = Image::fromFile($fotoUpload);

		if ($crop)
		{
			if (!is_array($crop) || count($crop) != 4)
			{
				throw new ImageServiceException("Oříznutí musí být pole o čtyřech prvcích [x,y,w,h]!");
			}

			list($x, $y, $w, $h) = $crop;
			$image->crop($x, $y, $w, $h);
		}

		if ($width || $height)
		{
			$image->resize($width, $height, $flags);
		}

		$this->onBeforeSaveThumbnail($this, $image);
		$image->save($this->getCacheDir() . $this->getCacheFilename($filename, $width, $height, $flags, $crop));

		return $image;
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
		return str_replace('//', '/', $this->cacheUrl . "/");
	}


	/**
	 * Vrátí název souboru pro keš
	 *
	 * @param string     $filename
	 * @param int|null   $width
	 * @param int|null   $height
	 * @param int|string $flags
	 * @param int[]|null $crop ořez obrázku x,y,w,h
	 *
	 * @return mixed|string
	 */
	private function getCacheFilename($filename, $width, $height, $flags, $crop = null)
	{
		$fileInfo = pathinfo($this->getUploadDir() . $filename);

		$cachename = str_replace(".", "-", $fileInfo['filename']);
		$cachename .= "_{$width}x{$height}_{$flags}";
		if ($crop)
		{
			$cachename .= '_'.implode('-', $crop);
		}

		$cachename .= "." . $fileInfo['extension'];

		return $cachename;
	}


//	/**
//	 * Vrátí typ originálního obrázku podle názvu
//	 *
//	 * @param $filename
//	 *
//	 * @return int
//	 */
//	private function getImageType($filename)
//	{
//		$file = $this->getUploadDir() . $filename;
//
//		switch (strtolower(pathinfo($file, PATHINFO_EXTENSION)))
//		{
//			case 'jpg':
//			case 'jpeg':
//				$type = Image::JPEG;
//				break;
//			case 'png':
//				$type = Image::PNG;
//				break;
//			case 'gif':
//				$type = Image::GIF;
//				break;
//			default:
//				throw new InvalidArgumentException("Unsupported image type.");
//		}
//
//		return $type;
//	}

	/**
	 * Uloží uploadnutý obrázek a vrátí jeho relativní cestu
	 *
	 * @param FileUpload $file
	 * @param string     $dir
	 *
	 * @return string
	 */
	public function upload(FileUpload $file, $filename = null)
	{

		// berem jenom cajk obrázky
		if (!$file->isOk())
		{
			return null;
		}

		if (!$filename)
		{
			$content = $file->getContents();
			$filename = md5($content) . '.' . $this->getExtensionFromContent($content);
		}

		$path = $this->getUploadDir() . $filename;

		$file->move($path);

		return $filename;
	}


	/**
	 * @param string|Image $content
	 * @param null         $filename
	 *
	 * @return string
	 */
	public function save($content, $filename = null)
	{
		if (!$filename)
		{
			$filename = md5($content) . '.' . $this->getExtensionFromContent($content);
		}

		$path = $this->getUploadDir() . $filename;

		if ($content instanceof Image)
		{
			$content->save($path);
		}
		else
		{
			file_put_contents($path, $content);
		}

		return $filename;
	}


	/**
	 * Vrátí MIME typ podle obsahu
	 *
	 * @param $content
	 *
	 * @return string
	 */
	public function getMimeTypeFromContent($content)
	{
		return finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $content);
	}


	/**
	 * Vrátí příponu obrázku podle jeho binárního obsahu
	 *
	 * @param $content
	 *
	 * @return string
	 * @throws ImageServiceException
	 */
	public function getExtensionFromContent($content)
	{
		$mime = $this->getMimeTypeFromContent($content);
		$extensions = [
			'image/png'  => 'png',
			'image/jpeg' => 'jpg',
			'image/gif'  => 'gif',
		];

		if (!isset($extensions[$mime]))
		{
			throw new ImageServiceException('Not supported image MIME type "' . $mime . '"');
		}

		return $extensions[$mime];
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