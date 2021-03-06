<?php

namespace App\Module\Front\Serviceteam\Presenters;

use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\PersonsRepository;

/**
 * Class ServiceteamAuthBasePresenter
 * @package App\Module\Front\Serviceteam\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
abstract class ServiceteamAuthBasePresenter extends \App\Module\Front\Presenters\FrontBasePresenter
{

	/** @var Serviceteam */
	public $me;

	/** @var PersonsRepository @inject */
	public $persons;


	/**
	 * Začátek životního cyklu
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
			$this->flashMessage('Už si zaregistrovaný v této skupině. Nemůžeš se registrovat znovu!', 'warning');
			$this->redirect(':Front:Participants:Homepage:');
		}
		// Pokud je servisak => presmerujeme na jeho Homepage
		elseif ($this->user->isInRole(Person::TYPE_UNSPECIFIED))
		{
			$this->flashMessage('Ještě nemáš zvoleno čím budeš!', 'warning');
			$this->redirect(':Front:Unspecified:');
		}

		/** @var Serviceteam */
		$me = $this->serviceteams->find($this->getUser()->getId());
		if(!$me)
		{
			$this->getUser()->logout(true);
			$this->redirect(":Front:Login:", array('back' => $this->storeRequest()));
		}

		$this->me = $me;
		$this->template->me = $this->me;
	}

}
