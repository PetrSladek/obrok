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
	public function actionAvatars($onlyFill = 0, $size = 1024)
	{
		set_time_limit (0);
	    $query = new ServiceteamQuery();

        $serviceteams = $this->serviceteamRepository->fetch($query);
        
        $avatars = [];
        foreach($serviceteams as $serviceteam)
        {
		if ($onlyFill && !$serviceteam->getAvatar())
		{
			continue;
		}
		
          $avatars[] = [
            'id' => $serviceteam->getId(),
            'url' => $serviceteam->getAvatar() ? 'https://registrace.obrok19.cz' . $this->images->getImageUrl($serviceteam->getAvatar(), $size, $size, Image::EXACT, $serviceteam->getAvatarCrop()) : null,
          ];
        }
      

        $this->sendJson($avatars);
	}

}
