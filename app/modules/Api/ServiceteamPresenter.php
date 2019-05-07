<?php

namespace App\Module\Api\Presenters;

use App\Forms\GroupForm;
use App\Forms\IGroupFormFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Forms\Form;
use App\Model\Entity\Person;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use App\Query\ServiceteamQuery;
use App\Query\ProgramsQuery;
use App\Query\ProgramsSectionsQuery;
use App\Services\ImageService;
use Nette\Utils\AssertionException;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Image;

/**
 * Class ServiceteamPresenter
 * @package App\Module\Api\Presenters
 */
class ServiceteamPresenter extends \Nette\Application\UI\Presenter
{

    /**
     * @var ServiceteamRepository
     * @inject
     */
    public $serviceteamRepository;
    
    	/** @var ImageService @inject */
	public $images;

   
    /**
     * Vrátí avatary ST
     *
     * @param null $sectionId
     * @throws \Nette\Application\AbortException
     */
	public function actionAvatars()
	{
	    $query = new ServiceteamQuery();

        $serviceteams = $this->serviceteamRepository->fetch($query);
        
        $avatars = [];
        foreach($serviceteams as $serviceteam)
        {
          $avatars[] = [
            'id' => $serviceteam->getId(),
            'url' => $this->images->getImageUrl($serviceteam->getAvatarUrl(), 800, 800, Image::EXACT, $serviceteam->getAvatarCrop()),
          ];
        }
      

        $this->sendJson($avatars);
	}

}
