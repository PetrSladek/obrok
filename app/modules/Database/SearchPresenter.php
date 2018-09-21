<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\PersonsRepository;
use App\Query\ParticipantsQuery;
use App\Query\ServiceteamQuery;
use App\Model\Repositories\JobsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Model\Repositories\TeamsRepository;
use App\Model\Repositories\WorkgroupsRepository;
use App\Services\ImageService;

use Brabijan\Images\TImagePipe;
use Kdyby\Doctrine\EntityRepository;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Http\IResponse;
use Nette\Security\Passwords;

use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;

/**
 * Class SearchPresenter
 *
 * @author  psl <petr.sladek@webnode.com>
 */
class SearchPresenter extends DatabaseBasePresenter
{

	/**
	 * @var string
	 * @persistent
	 */
	public $query;


	/**
	 * @var PersonsRepository
	 * @inject
	 */
	public $personsRepository;


	public function renderDefault()
	{
		$query = new ServiceteamQuery();
		$query->searchFulltext($this->query);
		$result = $this->personsRepository->fetch($query);

		$this->template->serviceteam = $result;


		$query = new ParticipantsQuery();
		$query->searchFulltext($this->query);
		$result = $this->personsRepository->fetch($query);

		$this->template->participants = $result;
	}
}



