<?php

namespace App\Module\Database\Presenters;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Workgroup;
use App\Model\Repositories\SettingsRepository;
use App\Query\GroupsQuery;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Model\Repositories\TeamsRepository;
use App\Model\Repositories\WorkgroupsRepository;
use Kdyby\Doctrine\EntityRepository;
use Nette\Application\UI\Form;
use Nette\Http\IResponse;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

/**
 * Class DashboardPresenter
 * @package App\Module\Database\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class DashboardPresenter extends DatabaseBasePresenter
{

	/** @var TeamsRepository @inject */
	public $teams;

	/** @var WorkgroupsRepository @inject */
	public $workgroups;

	/** @var GroupsRepository @inject */
	public $groups;

	/** @var ParticipantsRepository @inject */
	public $participants;

	/** @var ServiceteamRepository @inject */
	public $serviceteams;


    /**
	 * Výchozí akce
	 */
	public function renderDefault()
	{
        // otevřená registrace v settings
        $this->template->openRegistrationParticipantsSettings = $this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true); // default TRUE

		$this->template->lastServiceteam = $this->serviceteams->findBy([], ['createdAt' => 'desc'], 10);
		$this->template->lastGroups = $this->groups->findBy([], ['createdAt' => 'desc'], 10);
		$this->template->lastParticipants = $this->participants->findBy([], ['createdAt' => 'desc'], 10);

		$serviceteam = new ArrayHash();
		$serviceteam->confirmed = $this->serviceteams->countBy(['confirmed' => 1]);
		$serviceteam->paid = $this->serviceteams->countBy(['paid' => 1]);
		$serviceteam->arrived = $this->serviceteams->countBy(['arrived' => 1]);
		$serviceteam->left = $this->serviceteams->countBy(['left' => 1]);

		$this->template->serviceteam = $serviceteam;

		$groups = new ArrayHash();

		$query = (new GroupsQuery())->onlyConfirmed();
		$groups->confirmed = $this->groups->fetch($query)->count();

		$query = (new GroupsQuery())->onlyPaid();
		$groups->paid = $this->groups->fetch($query)->count();

		$query = (new GroupsQuery())->onlyArrived();
		$groups->arrived = $this->groups->fetch($query)->count();

		$query = (new GroupsQuery())->onlyLeft();
		$groups->left = $this->groups->fetch($query)->count();

		$this->template->groups = $groups;

		$participants = new ArrayHash();
		$participants->confirmed = $this->participants->countBy(['confirmed' => 1]);
		$participants->paid = $this->participants->countBy(['paid' => 1]); // zaplacenych muze byt vic nez tech co prijedou
		$participants->arrived = $this->participants->countBy(['confirmed' => 1, 'arrived' => 1]);
		$participants->left = $this->participants->countBy(['confirmed' => 1, 'left' => 1]);

		$this->template->participants = $participants;

		//tricka
		$tshirts = new ArrayHash();
		foreach (Serviceteam::$tShirtSizes as $size => $sizeName)
		{
			$tshirts[$sizeName] = $this->serviceteams->countBy(['confirmed' => 1, 'tshirtSize' => $size]);
		}
		$this->template->tshirts = $tshirts;

		// Problemovy skupiny
		$warningGroups = new ArrayHash();

        // malo lidi
        $query = (new GroupsQuery())
            ->onlyConfirmed()
            ->hasCountParticipantsLessThen(4);
		$warningGroups->fewParticipants = $this->groups->fetch($query);

		// chybi vedouci
        $query = (new GroupsQuery())
            ->onlyConfirmed()
            ->hasCountParticipantsAtLeast(4)
            ->hasNoBoss();
        $warningGroups->noBoss =  $this->groups->fetch($query);

        // existuje v ní nezaplacený účastník
        $query = (new GroupsQuery())
            ->onlyPaid()
            ->hasUnpaidParticipants();
		$warningGroups->paidWithUnpaidParticipant = $this->groups->fetch($query);

        // skupina je nezaplacená ale je v ní nějaký zaplacený účastník
        $query = (new GroupsQuery())
            ->onlyConfirmed()
            ->onlyUnpaid()
            ->hasPaidParticipants();
		$warningGroups->confirmedWithPaidParticipant = $this->groups->fetch($query);


		$this->template->warningGroups = $warningGroups;

		// Graf ST
		$serviceteam = $this->serviceteams->findBy(['confirmed' => 1]);
		$chartData = array();
		foreach ($serviceteam as $item)
		{
			$teamId = (int) ($item->team ? $item->team->id : 0);
			if (!isset($chartData[$teamId]))
			{
				$chartData[$teamId] = array('count' => 0, 'name' => $item->team ? $item->team->name : 'Nezařazen', 'abbr' => $item->team ? $item->team->abbr : '');
			}
			$chartData[$teamId]['count']++;
		}
		usort($chartData, function ($a, $b)
		{
			return ($a['count'] < $b['count']);
		});
		$this->template->chartServiceteam = $chartData;

		// Graf Krajů
		/** @var Group[] $groups */
		$groups = $this->groups->findAll(); //TODO findBy(['confirmed' => 1]);
		$chartData = array();
		foreach ($groups as $item)
		{
			if (!isset($chartData[$item->region]))
			{
				$chartData[$item->region] = array('count' => 0, 'name' => $item->region, 'count_participants' => 0);
			}
			$chartData[$item->region]['count']++;
			$chartData[$item->region]['count_participants'] += $item->getConfirmedParticipantsCount();
		}
		usort($chartData, function ($a, $b)
		{
			return ($a['count'] < $b['count']);
		});
		$this->template->chartRegions = $chartData;

	}


	/**
	 * Přkaz k otevření registrace
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleOpenParticipantRegistration()
	{
		if (!$this->user->isInRole('groups-edit'))
		{
			$this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
		}

		$this->settings->set(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true);
        $this->openRegistrationParticipants &= true; // bere se tam jeste kapacita

		$this->flashMessage("Registrace účastníků byla otevřena", "success");
		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


    /**
     * Příkaz k zavření registrace
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function handleCloseParticipantRegistration()
    {
        if (!$this->user->isInRole('groups-edit'))
        {
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
        }

        $this->settings->set(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, false);
        $this->openRegistrationParticipants = false;

        $this->flashMessage("Registrace účastníků byla uzavřena", "success");
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }


    /**
     * Příkaz k otevření registrace ST
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function handleOpenServiceteamRegistration()
    {
        if (!$this->user->isInRole('serviceteam-edit'))
        {
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
        }

        $this->settings->set(self::OPEN_SERVICETEAM_REGISTRATION_KEY, true);
        $this->openRegistrationServiceteam = true;

        $this->flashMessage("Registrace servis týmu byla otevřena", "success");
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }


    /**
     * Příkaz k zavření registrace
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function handleCloseServiceteamRegistration()
    {
        if (!$this->user->isInRole('serviceteam-edit'))
        {
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
        }

        $this->settings->set(self::OPEN_SERVICETEAM_REGISTRATION_KEY, false);
        $this->openRegistrationServiceteam = false;

        $this->flashMessage("Registrace servis týmu byla uzavřena", "success");
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }

    /**
     * Příkaz k otevření registrace Programů
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function handleOpenProgramRegistration()
    {
        if (!$this->user->isInRole('groups-edit'))
        {
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
        }

        $this->settings->set(self::OPEN_PROGRAM_REGISTRATION_KEY, true);
        $this->openRegistrationProgram = true;

        $this->flashMessage("Registrace programů byla otevřena", "success");
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }


    /**
     * Příkaz k zavření registrace Programů
     *
     * @throws \Nette\Application\BadRequestException
     */
    public function handleCloseProgramRegistration()
    {
        if (!$this->user->isInRole('groups-edit'))
        {
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
        }

        $this->settings->set(self::OPEN_PROGRAM_REGISTRATION_KEY, false);
        $this->openRegistrationProgram = false;

        $this->flashMessage("Registrace programů byla uzavřena", "success");
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }


    /**
     * Formulář na zadání kapacity (účastníků) akce
     *
     * @return Form
     */
    public function createComponentFrmParticipantsCapacity()
    {
        $frm = new Form();

        $frm->addText('capacity', 'Maximální počet účastníků')
            ->setType('number')
            ->setRequired()
            ->addRule(Form::NUMERIC)
            ->setDefaultValue($this->participantsCapacity);

        $frm->addSubmit('send', 'Uložit');

        $frm->onSuccess[] = function (Form $frm, ArrayHash $values)
        {
            // ulozime hodnotu do nastaven8
            $this->settings->set(self::CAPACITY_PARTICIPANTS, (int) $values->capacity);
            $this->participantsCapacity = (int) $values->capacity;

            $this->flashMessage('Kapacita účastníků úspěšně uložena.', 'success');
            $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
        };

        return $frm;
    }

	/**
	 * Formulář na zadání kapacity (účastníků) akce
	 *
	 * @return Form
	 */
	public function createComponentFrmProgramRegistrationDateFrom()
	{
		$frm = new Form();

		$frm->addText('date', 'Registraci povolit od:')
			->setDefaultValue($this->programRegistrationDateFrom ? $this->programRegistrationDateFrom->format('j.n.Y H:i') : null);

		$frm->addSubmit('send', 'Uložit');

		$frm->onSuccess[] = function (Form $frm, ArrayHash $values)
		{
			// ulozime hodnotu do nastaven8

			$date = new DateTime($values->date);

			$this->settings->set(self::PROGRAM_REGISTRATION_DATE_FROM, serialize($date));
			$this->programRegistrationDateFrom = $date;

			$this->flashMessage('Datum registrace programů úspěšně uloženo.', 'success');
			$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
		};

		return $frm;
	}
}
