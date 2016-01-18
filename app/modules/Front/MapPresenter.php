<?php

namespace App\Module\Front\Presenters;

use App\Model\Repositories\GroupsRepository;

/**
 * Class MapPresenter
 * @package App\Module\Front\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class MapPresenter extends FrontBasePresenter
{

	/** @var GroupsRepository @inject */
	public $groups;


	/**
	 * Předá data pro vykreslení výchozí šablony
	 */
	public function renderDefault()
	{
		$list = $this->groups->findBy(['canceled' => false]);

		$this->template->groups = $list;
	}

}


;
