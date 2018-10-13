<?php

namespace App\Module\Front\Participants\Presenters;

use App\Forms\GroupRegistrationForm;
use App\Forms\IGroupRegistrationFormFactory;
use App\Forms\IParticipantRegistrationFormFactory;
use App\Model\Entity\Group;
use App\Model\Repositories\GroupsRepository;
use App\Module\Front\Presenters\UnspecifiedPersonAuthBasePresenter;

/**
 * Class RegistrationPresenter
 * @package App\Module\Front\Participants\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class RegistrationPresenter extends UnspecifiedPersonAuthBasePresenter
{

	/** @var GroupsRepository @inject */
	public $groups;

	/** @var Group */
	public $group;

	/** @var IGroupRegistrationFormFactory @inject */
	public $groupRegistrationFormFactory;

	/** @var IParticipantRegistrationFormFactory @inject */
	public $participantRegistrationFormFactory;


	/**
	 * Formulář pro registraci nové skupiny
	 * @return GroupRegistrationForm
	 */
	public function createComponentFrmRegistration()
	{

		$control = $this->groupRegistrationFormFactory->create();
		$control->onGroupRegistered[] = function ($_, Group $group)
		{
			$this->flashMessage('Skupina byla vytvořena! Ted se ještě musíš zaregistrovat ty :)', 'success');
			$this->redirect('toGroup', $group->getId(), $group->getInvitationHash($this->config->hashKey));
		};

		return $control;
	}


	/**
	 * Registrace do existující skupiny (z pozvánky nebo z presmerování po zalození skupiny)
	 *
	 * @param $id
	 * @param $hash
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function actionToGroup($id, $hash)
	{
		$this->group = $this->groups->find($id);

		if (!$this->group)
		{
			$this->error("Skupina #$id neexistuje");
		}

		if ($this->group->getInvitationHash($this->config->hashKey) !== $hash)
		{
			$this->error("Pokus o napadeni");
		}

        if (!$this->openRegistrationParticipants/* && !$this->group->getFreePlaces()*/) {
            $this->flashMessage('Nelze registrovat nové učastníky. Kapacita je již zaplněná', 'warning');
            $this->redirect('Homepage:');
        }

		$this->template->group = $this->group;
	}


	/**
	 * Formulař pro registraci účastníka
	 *
	 * @return \App\Forms\ParticipantRegistrationForm
	 */
	public function createComponentFrmParticipantRegistration()
	{
		$control = $this->participantRegistrationFormFactory->create($this->me->getId(), $this->group->getId());
		$control->setAgeInDate($this->ageInDate);
		$control->onParticipantRegistred[] = function ($_, $person, $group)
		{
			// $person === $this->me

			$mail = $this->emails->create('participantFirstInfo', 'První informace');
			$mail->addTo($person->email);
			$this->emails->send($mail);

			// Zmenila se mi role
			$this->user->login($person->toIdentity());

			$this->redirect('Homepage:');
		};

		return $control;
	}

}


