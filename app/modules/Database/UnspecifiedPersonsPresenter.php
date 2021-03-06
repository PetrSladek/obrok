<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Program;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\UnspecifiedPersonsRepository;
use App\Query\ParticipantsQuery;
use App\Query\ProgramsQuery;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use App\Query\UnspecifiedPersonsQuery;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Nette\Http\IResponse;
use Nette\InvalidStateException;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;

/**
 * Class UnspecifiedPresenter
 *
 * @package App\Module\Database\Presenters
 * @author  psl <petr.sladek@webnode.com>
 * @persistent(tblGrid)
 */
class UnspecifiedPersonsPresenter extends DatabaseBasePresenter
{

	/** @var UnspecifiedPersonsRepository @inject */
	public $repository;

    /**
     * @var PersonsRepository @inject
     */
	public $persons;

	/** @var GroupsRepository @inject */
	public $groups;

	/** @var UnspecifiedPerson */
	public $item;


	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();
		$this->acl->edit = $this->user->isInRole('groups-edit');
	}


	/**
	 * Továrna na komponentu tabulky
	 *
	 * @return Datagrid
	 */
	protected function createComponentTblGrid()
	{
		$grid = new Datagrid();

		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/templates/grid.layout.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Participants/grid.cols.latte');

		$grid->addColumn('id', 'Id')->enableSort();
		$grid->addColumn('fullname', 'Jméno')->enableSort();

		$grid->addColumn('age', 'Věk')->enableSort();
		$grid->addColumn('contact', 'Kontakt')->enableSort();
		$grid->addColumn('address', 'Adresa')->enableSort();

		$grid->setFilterFormFactory(function ()
		{

			$form = new Container();
			$form->addText('id');
			$form->addText('fullname');
			$form->addText('age');
			$form->addText('contact');
			$form->addText('address');

			// these buttons are not compulsory
			$form->addSubmit('filter', 'Vyfiltrovat');
			$form->addSubmit('cancel', 'Zrušit');

			return $form;
		});

		$grid->addGlobalAction('export', 'Export', function (array $ids, Datagrid $grid)
		{
			$this->redirect('export', [
				'ids' => array_values($ids),
				'filename' => 'export-nezucastneni-' . date('YmdHis') . '.csv'
			]);
		});


		$grid->setPagination($this->gridItemsPerPage, function ($filter)
		{
			$query = $this->getFilteredQuery($filter);

			return $query->count($this->repository);
		});

		$grid->setDataSourceCallback(function ($filter, $sorting, Paginator $paginator = null)
		{

			$query = $this->getFilteredQuery($filter);
			$result = $this->repository->fetch($query);

			if ($paginator)
			{
				$result->applyPaging($paginator->getOffset(), $paginator->getLength());
			}

            if($sorting)
            {
                list($column, $order) = $sorting;

                if ($column == 'fullname')
                {
                    $column = 'p.lastName';
                }
                else if ($column == 'group')
                {
                    $column = 'g.name';
                }
                else if ($column == 'age')
                {
                    $column = 'p.birthdate';
                    $order = $order == 'ASC' ? 'DESC' : 'ASC';
                }
                else if ($column == 'contact')
                {
                    $column = 'p.email';
                }
                else if ($column == 'address')
                {
                    $column = 'p.addressCity';
                }
                else
                {
                    $column = 'p.' . $column;
                }

                $result->applySorting([$column => $order]);
            }

			return $result;
		});

		$grid->onRender[] = function($grid)
		{
			$grid->template->imageService = $this->imageService;
		};

		return $grid;
	}


	/**
	 * @param array $filter
	 *
	 * @return UnspecifiedPersonsQuery
	 */
	public function getFilteredQuery($filter)
	{
		$query = new UnspecifiedPersonsQuery();

		foreach ($filter as $key => $val)
		{
			if ($key == 'id')
			{
				$query->byId($val);
			}
			elseif ($key == 'fullname')
			{
				$query->searchFullname($val);
			}
			elseif ($key == 'age')
			{
				$query->byAge($val, $this->ageInDate);
			}
			elseif ($key == 'contact')
			{
				$query->searchContact($val);
			}
			elseif ($key == 'address')
			{
				$query->searchAddress($val);
			}
		}

		return $query;
	}


	/**
	 * Detail nezučastněného
	 *
	 * @param int $id
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function actionDetail($id)
	{
		$this->item = $this->repository->find($id);
		if (!$this->item)
		{
			$this->error("Item not found");
		}
	}




	/**
	 * Vykreslení šavlony detailu nezučastněného
	 *
	 * @param int $id
	 */
	public function renderDetail($id)
	{
		$this->template->item = $this->item;
	}



    /**
     * Přihlásit se na FrontEnd jako účastník
     *
     * @param $id
     *
     * @throws \Nette\Application\AbortException
     * @throws \Nette\Application\BadRequestException
     */
	public function actionLoginAs($id)
	{
		if (!$this->acl->edit)
		{
			$this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);
		}

		$this->item = $this->repository->find($id);
		if (!$this->item)
		{
			$this->error("Item not found");
		}

		$hash = Random::generate(22, '0-9A-Za-z./');
		$this->item->setQuickLoginHash(Passwords::hash($hash));

		$this->em->persist($this->item);
		$this->em->flush();

		$this->redirect(":Front:Login:as", $id, $hash);
	}


    /**
     * @throws \Exception
     */
    public function createComponentFrmToParticipant()
    {
        $frm = new Form();
        $frm->addGroup('Převést na účastníka');
        $frm->addSelect('group', 'Zařadit do skupiny',
            array_map(function (Group $data) {
                return '#' . $data->getId() . ' ' . $data->getName();
            }, $this->groups->findAssoc([], 'id')))
            ->setPrompt('- Vyberte skupinu -')
            ->setRequired();

        $frm->addSubmit('send', 'Převést')->setAttribute('class', 'btn btn-success');

        $frm->onSuccess[] = [$this, 'frmToParticipantSubmitted'];

        return $frm;
    }

    /**
     * Akce po odeslání formuláře
     *
     * @param Form $frm
     *
     * @throws \Nette\Application\AbortException
     */
    public function frmToParticipantSubmitted(Form $frm)
    {
        $values = $frm->getValues();

        /** @var Participant $participant */
        $participant = $this->persons->changePersonTypeTo($this->item, Person::TYPE_PARTICIPANT);

        /** @var Group $group */
        $group = $this->groups->find($values->group);
        if (!$group)
        {
            throw new \RuntimeException('Skupina "' . $values->group . '" neexistuje!');
        }

        $participant->setGroup($group);
        $this->em->flush($participant);

        $this->flashMessage('Nezúčastněná osoba byla převedena na účastníka skupiny #' . $group->getId() . ' ' . $group->getName());
        $this->redirect("Participants:detail", $this->item->getId());
    }


    /**
     * @throws \Exception
     */
    public function createComponentFrmToServiceteam()
    {
        $frm = new Form();
        $frm->addGroup('Převést na servisáka');

        $frm->addSubmit('send', 'Převést')->setAttribute('class', 'btn btn-success');

        $frm->onSuccess[] = [$this, 'frmToServiceteamSubmitted'];

        return $frm;
    }

    /**
     * Akce po odeslání formuláře
     *
     * @param Form $frm
     *
     * @throws \Nette\Application\AbortException
     */
    public function frmToServiceteamSubmitted(Form $frm)
    {
        $values = $frm->getValues();

        /** @var Serviceteam $serviceteam */
        $serviceteam = $this->persons->changePersonTypeTo($this->item, Person::TYPE_SERVICETEAM);

        $this->em->flush($serviceteam);

        $this->flashMessage('Nezúčastněná osoba byla převedena na servisáka');
        $this->redirect("Serviceteam:detail", $this->item->getId());
    }


	/**
	 * @param bool $force
	 * @throws \Nette\Application\AbortException
	 */
	public function actionSendPaymentInstruction($force = false)
	{
		set_time_limit(0);

		$query = new UnspecifiedPersonsQuery();
		$query->onlyNotSentParticipantInfo();

		$result = $this->repository->fetch($query);
		$result->applyPaging(0, 100);

		$sent = 0;

		if (!$force)
		{
			$unspecified = new UnspecifiedPerson();
			$unspecified->setFullName('Test', 'Testovic', 'Testov');
			$unspecified->setEmail('peggy@skaut.cz');
			$result = [$unspecified];
		}

		$failed = [];
		/** @var Serviceteam $unspecified */
		foreach ($result as $unspecified)
		{
			try {
				$mail = $this->emails->create(
					'unspecifiedPaymentInstruction',
					'Info',
					[],
					$this
				);
				$mail->addTo($unspecified->getEmail(), $unspecified->getFullname());

				$this->emails->send($mail);

				$unspecified->setSentPaymentInfoEmail(true);
				$this->em->flush();

				$sent++;
			}
			catch (\Exception $e)
			{
				$failed[$unspecified->getEmail()] = $e;
			}
		}


		echo "Odeslano $sent emailu\n";

		foreach ($failed as $email => $e)
		{
			echo "Nepodarilo se odslat $email: {$e->getMessage()}\n";
		}

		$this->terminate();
	}

}



