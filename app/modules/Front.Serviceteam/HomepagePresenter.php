<?php

namespace App\Module\Front\Serviceteam\Presenters;

use App\Forms\Form;
use App\Forms\IServiceteamAdditionalFormFactory;
use App\Forms\IServiceteamFormFactory;
use App\Forms\ServiceteamForm;
use App\Model\Entity\Person;
use App\Services\ImageService;
use Kdyby\Doctrine\EntityRepository;
use Nette\Utils\DateTime;

/**
 * Class HomepagePresenter
 * @package App\Module\Front\Serviceteam\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class HomepagePresenter extends ServiceteamAuthBasePresenter
{

	/** @var ImageService @inject */
	public $images;

	/** @var EntityRepository */
	public $serviceteam;

	/** @var IServiceteamFormFactory @inject */
	public $serviceteamFormFactory;

	/** @var IServiceteamAdditionalFormFactory @inject */
	public $serviceteamAdditionalFormFactory;


    /**
     * Vykreslí stránku s nástenkou
     */
    public function renderDefault()
    {
        $payToDate = DateTime::from($this->me->getCreatedAt());
        $payToDate->modify('+ 30 days midnight');

        $now = new DateTime('now midnight');

        $diff = $now->diff($payToDate);
        $this->template->daysToPay = $payToDate > $now ? $diff->days : 0;
    }


	/**
	 * Formulář pro editaci údajů
	 * @return \App\Forms\ServiceteamForm
	 */
	public function createComponentFrmEdit()
	{

		$control = $this->serviceteamFormFactory->create($this->me->getId());

		$control->onSave[] = function (ServiceteamForm $form, Person $person)
		{
			$this->flashMessage('Údaje úspěšně upraveny', 'success');
			$this->redirect('default');
		};

		$control->onCancel[] = function (ServiceteamForm $form)
		{
			$this->redirect('default');
		};

		return $control;
	}


	/**
	 * Formulář pro zrušení účasti
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

		$frm->onSuccess[] = function (Form $frm)
		{

			$this->me->confirmed = false; // neprijede
			$this->me->noteInternal .= "\nDůvod zrušení učasti: " . $frm->getValues()->reason;

			$this->em->persist($this->me);
			$this->em->flush();

			$this->flashMessage('Tvoje účast na obroku byla úspěšně zrušena. Tento účet zůstane aktivní, ale už se s tebou na Obroku nepočítá.');
			$this->redirect('Homepage:');
		};

		return $frm;
	}


	/**
	 * Formulář pro dokončení registrace (doplňující údaje)
	 * @return \App\Forms\ServiceteamAdditionalForm
	 */
	public function createComponentFrmAdditional()
	{
		$control = $this->serviceteamAdditionalFormFactory->create($this->me->getId());
		$control->onAdditionalSave[] = (function ($control, $person)
		{
			$this->flashMessage('Doplňující údaje úspěšně přidány', 'success');
			$this->redirect('Homepage:');
		});

		return $control;
	}


	/**
	 * Převést přihlášeného uživatele na nezařazenou osobu
	 */
	public function handleToUnspecifiedPerson()
	{

		$this->persons->changePersonTypeTo($this->me, Person::TYPE_UNSPECIFIED);

		$this->user->login($this->me->toIdentity());

		$this->flashMessage('Tvá účast v ST je zrušena');
		$this->redirect(":Front:Unspecified:");
	}

}


;