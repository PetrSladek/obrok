<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Repositories\PersonsRepository;

/**
 * Class ParticipantAuthBasePresenter
 * @package App\Module\Front\Participants\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
abstract class ParticipantAuthBasePresenter extends \App\Module\Front\Presenters\FrontBasePresenter
{

	/** @var Participant */
	public $me;

	/** @var PersonsRepository @inject */
	public $persons;

	/**
	 * Je otevřena registrace?
	 * @var bool
	 */
	protected $open = true;


	/**
	 * Začátek životního cyklu presenteru
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
		elseif ($this->user->isInRole(Person::TYPE_SERVICETEAM))
		{
			$this->flashMessage('Už si zaregistrovaný jako servisák. Nemůžeš se registrovat znovu!', 'warning');
			$this->redirect(':Front:Serviceteam:Homepage:');
		}
		// Pokud je servisak => presmerujeme na jeho Homepage
		elseif ($this->user->isInRole(Person::TYPE_UNSPECIFIED))
		{
			$this->flashMessage('Ještě nemáš zvoleno čím budeš!', 'warning');
			$this->redirect(':Front:Unspecified:');
		}

		/** @var Participant */
		$me = $this->persons->find($this->getUser()->getId());
		if(!$me)
		{
			$this->getUser()->logout(true);
			$this->redirect(":Front:Login:", array('back' => $this->storeRequest()));
		}

		$this->me = $me;
		$this->template->me = $this->me;

		// Uzavreni registrace, aby se v prubehu obroku{stavecky uz nedfali menit yadna data ye stranz ucastniku
		$this->open = true;
		$this->template->open = $this->open;
	}

}
