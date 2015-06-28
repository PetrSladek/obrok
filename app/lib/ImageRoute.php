<?php

namespace Myann;

use App\Services\ImageService;
use Nette;

class ImageRouter implements \Nette\Application\IRouter
{

    /** @var ImageService */
    protected $images;

    public function __construct(ImageService $images) {
        $this->images = $images;
    }

    /**
     * Maps HTTP request to a Request object.
     * @return Request|NULL
     */
    function match(Nette\Http\IRequest $httpRequest) {
        return null;
    }

    /**
     * Constructs absolute URL from Request object.
     * @return string|NULL
     */
    public function constructUrl(Nette\Application\Request $appRequest, Nette\Http\Url $refUrl)
    {
        $params = $appRequest->getParameters();

        list($module, $presenter) = explode(':',$appRequest->getPresenterName());
        // $params[self::PRESENTER_KEY] = $presenter;
       $action = $params['action'];

        if($presenter == 'Image' && $action == 'default' && !empty($params['filename'])) {
            $filename = $params['filename'];
            $width = isset($params['width']) ? $params['width'] : null;
            $height = isset($params['height']) ? $params['height'] : null;
            $flags = isset($params['flags']) ? $params['flags'] : Nette\Utils\Image::FIT;
            $crop = isset($params['crop']) ? $params['crop'] : null;
            try {
                return $this->images->getImageUrl($filename, $width, $height, $flags, $crop);
            } catch (ImageServiceException $e) {
                return '#'.$e->getMessage();
            }

        }

        return null;
    }

}