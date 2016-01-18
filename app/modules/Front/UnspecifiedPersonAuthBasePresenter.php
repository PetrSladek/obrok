<?php

namespace App\Module\Front\Presenters;

use App\Model\Entity\UnspecifiedPerson;
use App\Model\Entity\Person;
use App\Model\Repositories\PersonsRepository;

/**
 * Class UnspecifiedPersonAuthBasePresenter
 * @package App\Module\Front\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
abstract class UnspecifiedPersonAuthBasePresenter extends \App\Module\Base\Presenters\BasePresenter
{
	/** @var UnspecifiedPerson */
	public $me;

	/** @var PersonsRepository @inject */
	public $persons;


	/**
	 * Před spuštěním
	 */
	public function startup()
	{
		parent::startup();

		// Kdyz neni prihlaseny vubec => donutime ho se prihlasit
		if (!$this->getUser()->isLoggedIn())
		{
			$this->redirect(":Front:Login:", array('back' => $this->storeRequest()));
		}
		// Pokud je ucatnik => presmerujeme na jeho Homepage
		elseif ($this->user->isInRole(Person::TYPE_PARTICIPANT))
		{
			$this->flashMessage('Už si zaregistrovaný jako účastník. Nemůžeš se registrovat znovu!', 'danger');
			$this->redirect(':Front:Participants:Homepage:');
		}
		// Pokud je servisak => presmerujeme na jeho Homepage
		elseif ($this->user->isInRole(Person::TYPE_SERVICETEAM))
		{
			$this->flashMessage('Už si zaregistrovaný jako servisák. Nemůžeš se registrovat znovu!', 'danger');
			$this->redirect(':Front:Serviceteam:Homepage:');
		}

		/** @var UnspecifiedPerson */
		$me = $this->persons->find($this->getUser()->getId());
		$this->me = $me;
		$this->template->me = $this->me;
	}

}
