<?php

namespace App\Module\Front\Participants\Presenters;

use App\Forms\IGroupFormFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Forms\Form;
use App\Model\Entity\Person;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use App\Model\Repositories\GroupsRepository;
use App\Services\ImageService;
use Nette\Utils\AssertionException;
use Nette\Utils\DateTime;

/**
 * Class HomepagePresenter
 * @package App\Module\Front\Participants\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class HomepagePresenter extends ParticipantAuthBasePresenter
{

	/** @var ImageService @inject */
	public $images;

	/** @var GroupsRepository @inject */
	public $groups;

	/** @var Participant */
	public $participant;

	/** @var IGroupFormFactory @inject */
	public $groupFormFactory;


	/**
	 * Připravý data pro vypsání výchozí šablony
	 */
	public function renderDefault()
	{
		$programs = $this->me->getPrograms();
		$programs = array_filter($programs, function (Program $program)
		{
			return $program->section->getId() !== ProgramSection::KRINSPIRO; // Krinspiro
		});

		$this->template->programs = $programs;

        $fromDate = new DateTime('2019-01-25 20:00');

        $payToDate = DateTime::from($fromDate);
        $payToDate->modify('+ 30 days midnight');

        $now = new DateTime('now midnight');

        $diff = $now->diff($payToDate);
        $this->template->daysToPay = $payToDate > $now ? $diff->days : 0;

	}


	/**
	 * Editace skupiny
	 */
	public function actionEditGroup()
	{
		if (!$this->me->isAdmin())
		{
			$this->flashMessage('Musíte být administrátorem skupiny, abyste mohl měnit její údaje!', 'danger');
			$this->redirect('Homepage:');
		}

		$this->template->data = $this->me->group;
	}


	/**
	 * Formulář pro editaci skupiny
	 * @return \App\Forms\GroupForm
	 */
	public function createComponentFrmEditGroup()
	{

		$control = $this->groupFormFactory->create($this->me->group->getId());
		$control->setAgeInDate($this->ageInDate);
		$control->onSave[] = function ($control, Group $group)
		{
			$this->flashMessage('Údaje úspěšně upraveny', 'success');
			$this->redirect('default');
		};

		return $control;
	}


	/**
	 * Vrátí účastníka zpět
	 *
	 * @param $id
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleGoBack($id)
	{
        if ($this->openRegistrationParticipants)
        {
            $this->flashMessage('Účastníka nelze vzít zpět. Registrace je uzavřená, kapacita byla naplněna.', 'danger');
            $this->isAjax() ? $this->redrawControl() : $this->redirect('this');

            return;
        }


		$this->participant = $this->participants->find($id);
		if (!$this->participant)
		{
			$this->error("Item not found");
		}
		if ($this->participant->group !== $this->me->group)
		{
			$this->error("Access denied");
		}

		$this->participant->setConfirmed(true);
		$this->em->flush();

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Vyhodí účastníka ze skupiny
	 *
	 * @param $id
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleGoOut($id)
	{

		$this->participant = $this->participants->find($id);
		if (!$this->participant)
		{
			$this->error("Item not found");
		}
		if ($this->participant->group !== $this->me->group)
		{
			$this->error("Access denied");
		}

		$this->participant->setConfirmed(false);
		$this->em->flush();

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	// ADD-EDIT PARTICIPANT
	/**
	 * Přidání nebo editace účastníka
	 *
	 * @param null $id
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function actionParticipant($id = null)
	{
		if ($id)
		{
			$this->participant = $this->participants->find($id);
			if (!$this->participant)
			{
				$this->error('Účastník neexistuje');
			}
			if ($this->participant->group->getId() != $this->me->group->getId())
			{
				$this->error('Účastník není z vaší skupiny');
			}
			if (!($this->participant->getId() == $id || $this->me->isAdmin()))
			{
				$this->error('Nejste administrator skupiny, muzete editovat jen sebe.');
			}
		}
		elseif (true)
		{
			$this->error('Nelze přidávat členy jinak, než zasláním pozvánky');
		}
		elseif (!$this->me->isAdmin())
		{
			$this->error('Členy může přidávat jen administrátor skupiny');
		}

		$this->template->item = $this->participant;
	}


	/**
	 * Vytvoří formulář pro editaci uživatele
	 *
	 * @return Form
	 */
	public function createComponentFrmParticipant()
	{

		$frm = new Form();
		$frm->addGroup('Osobní informace');

		$frm->addText('firstName', 'Jméno')
			->setDefaultValue($this->participant ? $this->participant->getFirstName() : null)
			->setRequired();
		$frm->addText('lastName', 'Příjmení')
			->setDefaultValue($this->participant ? $this->participant->getLastName() : null)
			->setRequired();
		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->participant ? $this->participant->getNickName() : null);

		$frm->addDatepicker('birthdate', 'Datum narození:')
			->setDefaultValue($this->participant ? $this->participant->getBirthdate() : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
			->addRule(Form::RANGE, 'Podle data narození vám 7.6.2019 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('7.6.2019')->modify('-15 years')))
			->addRule(Form::RANGE, 'Podle data narození vám 7.6.2019 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('7.6.2019')->modify('-25 years'), null));

//            ->addRule(callback('Participant','validateAge'), 'Věk účastníka Obroku 2015 musí být od 15 do 24 let');

		$frm->addRadioList('gender', 'Pohlaví', array('male' => 'muž', 'female' => 'žena'))
			->setDefaultValue($this->participant ? $this->participant->getGender() : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Trvalé bydliště');
		$frm->addText('addressStreet', 'Ulice a čp.')
			->setDefaultValue($this->participant ? $this->participant->getAddressStreet() : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->participant ? $this->participant->getAddressCity() : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressPostcode', 'PSČ')
			->setDefaultValue($this->participant ? $this->participant->getAddressPostcode() : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('email', 'E-mail')
			->setDefaultValue($this->participant ? $this->participant->getEmail() : null)
			->setEmptyValue('@')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
			->addRule(Form::EMAIL, 'E-mailová adresa není platná')
			->setAttribute('title', 'E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat. Budou Ti chodit informace atd..')
			->setAttribute('data-placement', 'right');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->participant ? $this->participant->getPhone() : null)
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			->setAttribute('title', 'Mobilní telefon, na kterém budeš k zastižení během celé akce')
			->setAttribute('data-placement', 'right');

		$frm->addGroup('Zdravotní omezení');
		$frm->addTextArea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->participant ? $this->participant->getHealth() : null);

		$frm->addGroup('Další infromace');
		$frm->addCheckbox('wantHandbook', 'Chci dostat tištěný handbook (sešit s programem, informacemi apod.)')
			->setDefaultValue($this->participant ? $this->participant->getWantHandbook() : null);


		// admin
		if ($this->me->isAdmin())
		{
			$frm->addCheckbox('admin', 'Administrátor skupiny')
				->setDefaultValue($this->participant ? (bool) $this->participant->isAdmin() : null);
		}

		$frm->addSubmit('send', 'Uložit údaje účastníka')
			->setAttribute('class', 'btn btn-primary');

		$frm->onSuccess[] = [$this, 'frmParticipantSubmitted'];

		if ($this->participant)
		{

			if ($this->participant->getId() != $this->me->getId())
			{
				$frm['email']->setAttribute('title', 'E-mail, který pravidelně vybírá. Budou tam chodit informace atd..');
				$frm['phone']->setAttribute('title', 'Mobilní telefon, na kterém bude k zastižení během celé akce');
			}
			$frm['firstName']->setDisabled()->setRequired(false)->setDefaultValue($this->participant->getFirstName());
			$frm['lastName']->setDisabled()->setRequired(false)->setDefaultValue($this->participant->getLastName());
		}

		return $frm;
	}


	/**
	 * Akce po odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmParticipantSubmitted(Form $frm)
	{
		$values = $frm->getValues();

		if (!$this->participant)
		{
			$this->participant = new Participant();
			$this->participant->setGroup($this->me->getGroup());

			$this->em->persist($this->participant);
		}

		foreach ($values as $key => $value)
		{
			$this->participant->$key = $value;
		}

		$this->em->flush();

		// Pokud sem to já aktualizuju objekt me
		if ($this->me->getId() == $this->participant->getId())
		{
			$this->me = $this->participant;
		}

		$this->flashMessage('Údaje úspěšně upraveny', 'success');
		if ($this->isAjax())
		{
			$this->redrawControl();
		}
		else
		{
			$this->redirect('default');
		}

	}


	/**
	 * Vytvoří formulář pro zrušení účastické skupiny
	 * @return Form
	 */
	public function createComponentFrmCancel()
	{

		$frm = new Form();

		$frm->addGroup(null);
		$frm->addTextArea('reason', 'Důvod zrušní účasti')
			->addRule(Form::FILLED, 'Prosím zadej důvod proč rušíš svou účast.');

		$frm->addSubmit('send', 'Ano opravdu na Obrok nepřijedu')
			->setAttribute('class', 'btn btn-primary');

		$frm->onSuccess[] = [$this, 'frmCancelSubmitted'];

		return $frm;
	}


	/**
	 * Akce po odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmCancelSubmitted(Form $frm)
	{

		// vsechny ucastniky oznacim jako ze neprijedou
		foreach ($this->me->group->getConfirmedParticipants() as $participant)
		{
			$participant->setConfirmed(false);
		}

		$this->me->group->noteInternal .= "\nDůvod zrušení učasti: " . $frm->values->reason;

		$this->em->flush();

		$this->flashMessage('Účast Vaší skupiny na Obroku byla zrušena. Účty zůstanou přístupné, ale už se s Vámi nepočítá!');
		$this->redirect('Homepage:');
	}


	/**
	 * Vytvoří formulář pro odeslání odkazu s pozvánkou
	 *
	 * @return Form
	 */
	public function createComponentFrmSendInvitationLink()
	{
		$frm = new Form();
		$frm->addTextArea('emails', 'E-mailové adresy')
			->setOption('description', 'Na každý řádek jednu e-mailovou adresu');

		$frm->addSubmit('send', 'Pozvat účastníky');

		$frm->onSuccess[] = [$this, 'frmSendInvitationLinkSuccess'];

		return $frm;
	}


	/**
	 * Akce po odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmSendInvitationLinkSuccess(Form $frm)
	{
		$values = $frm->getValues();
		$emails = array_map('trim', explode("\n", trim($values->emails)));

		$link = $this->link('//Invitation:toGroup', $this->me->group->getId(), $this->me->group->getInvitationHash($this->config->hashKey));

		try
		{
			$mail = $this->emails->create('participantInvitationLink', "Pozvánka do skupiny", array('group' => $this->me->group, 'link' => $link), $this);
			foreach ($emails as $to)
			{
				$mail->addTo($to);
			}
			$this->emails->send($mail);

			$this->flashMessage("E-maily s pozvánkou do skupiny jsou uspěšně rozeslány", 'success');
		}
		catch (AssertionException $e)
		{
			$frm['emails']->addError("Některý z emailů není ve správném tvaru", 'danger');

			return;
		}

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Zruší účast přihlášeního uživatele jako účastníák a stane se z něj nerozhodnutý
	 */
	public function handleToUnspecifiedPerson()
	{
		$group = $this->me->group;

		$group->removeParticipant($this->me);
		$group->tryDefineBoss();
		$group->tryDefineAdmin();

		$this->em->flush();

		// Zmenim typ na unspecified
		$this->persons->changePersonTypeTo($this->me, Person::TYPE_UNSPECIFIED);
		$this->user->login($this->me->toIdentity());

		$this->flashMessage('Tvá účast je zrušena');
		$this->redirect(":Front:Unspecified:");
	}

}


;
