<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Participant;
use App\Model\Entity\Program;
use App\Query\ParticipantsQuery;
use App\Query\ProgramsQuery;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use Latte\Template;
use Nette\Forms\Container;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Nette\Utils\Paginator;
use Nextras\Datagrid\Datagrid;

/**
 * Class ProgramPresenter
 * @package App\Module\Database\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class ProgramPresenter extends DatabaseBasePresenter
{

	/** @var ProgramsRepository @inject */
	public $repository;

	/** @var ParticipantsRepository @inject */
	public $participants;

	/**
	 * @var ProgramsSectionsRepository @inject
	 */
	public $sections;

	/** @var Program */
	public $item;


	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();
		$this->acl->edit = $this->user->isInRole('database'); // todo program-edit
	}


	/**
	 * Továrna na komponentu tabulky
	 *
	 * @return Datagrid
	 */
	public function createComponentTblGrid()
	{
		$grid = new Datagrid();

		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/templates/grid.layout.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Program/grid.cols.latte');

		$grid->addColumn('id', 'ID')->enableSort();
		$grid->addColumn('section', 'Sekce')->enableSort();

		$grid->addColumn('time', 'Den a čas')->enableSort();

		$grid->addColumn('name', 'Název')->enableSort();
		$grid->addColumn('capacity', 'Obsazeno');//->enableSort();

		$grid->setFilterFormFactory(function ()
		{
			$frm = new Container();
			$frm->addText('id')
				->addCondition(Form::FILLED)
				->addRule(Form::INTEGER);

			$sections = [];
			foreach ($this->sections->findAll() as $section)
			{
				$sections[$section->id] = $section->title . ($section->subTitle ? " - {$section->subTitle}" : null);
			}
			$frm->addMultiSelect('section', null, $sections);

			$frm->addText('name')
				->addCondition(Form::FILLED)
				->addRule(Form::INTEGER);

			$frm->addSelect('capacity', null, array(1 => 'Plné', 0 => 'Volné'))->setPrompt('--');

			// these buttons are not compulsory
			$frm->addSubmit('filter', 'Vyfiltrovat');
			$frm->addSubmit('cancel', 'Zrušit');

//            $form->setDefaults($this->filter);

			return $frm;
		});

		$grid->setPagination($this->gridItemsPerPage, function ($filter)
		{
			$query = $this->getFilteredQuery($filter);

			return $query->count($this->repository);
		});
		$grid->setDatasourceCallback(function ($filter, $sorting, Paginator $paginator = null)
		{

			$query = $this->getFilteredQuery($filter);

			$result = $this->repository->fetch($query);

			if ($paginator)
			{
				$result->applyPaging($paginator->getOffset(), $paginator->getLength());
			}
//            if($sorting) {
//                list($key, $val) = $sorting;
//                $result->applySorting([$key => $val]);
//            }

			return $result;
		});

		return $grid;
	}


	/**
	 * @param $filter
	 *
	 * @return ProgramsQuery
	 */
	public function getFilteredQuery($filter)
	{
		$query = new ProgramsQuery();

		foreach ($filter as $key => $val)
		{
			if ($key == 'id')
			{
				$query->byId($val);
			}
			elseif ($key == 'section')
			{
				$query->inSections($val);
			}
			elseif ($key == 'name')
			{
				$query->searchName($val);
			}
			elseif ($key == 'capacity')
			{
				$val ? $query->onlyFull() : $query->onlyNotFull();
			}
		}

		return $query;
	}


	/**
	 * Detail programu
	 *
	 * @param $id
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

		$this->template->item = $this->item;
	}


	/**
	 * Vykreslení šablony detailu
	 */
	public function renderDetail()
	{

		/** @var Datagrid $grid */
		if ($grid = $this->getComponent('tblAttendees', false))
		{
			/** @var Template $tpl */
			$tpl = $grid->getTemplate();

			$tpl->acl = $this->acl;
			$tpl->ageInDate = $this->ageInDate;
		}

	}


	/**
	 * Odhlášení ušastníka z programu
	 *
	 * @param $idParticipant
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleUnattendeeParticipant($idParticipant)
	{

		/** @var Participant $participant */
		$participant = $this->participants->find($idParticipant);
		if (!$participant)
		{
			$this->error('Ucastnik neexistuje');
		}

		$participant->unattendeeProgram($this->item);
		$this->em->flush();

		$this->flashMessage('Program úspěšně odhlášen.', 'success');

		if ($this->isAjax())
		{
			$this->redrawControl();
		}
		else
		{
			$this->redirect('this');
		}
	}


	/**
	 * Editace programu
	 *
	 * @param null $id
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function actionEdit($id = null)
	{
		if (!$this->acl->edit)
		{
			$this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);
		}

		if ($id)
		{
			$this->item = $this->repository->find($id);
			if (!$this->item)
			{
				$this->error("Item not found");
			}
		}
		$this->template->item = $this->item;
	}


	/**
	 * Továrna na komponentu editačního formuláře
	 * @return Form
	 */
	public function createComponentFrmEdit()
	{
		$frm = new Form();
		$frm->setAjax();

		$sections = [];
		foreach ($this->sections->findAll() as $section)
		{
			$sections[$section->id] = $section->title . ($section->subTitle ? " - {$section->subTitle}" : null);
		}

		$frm->addGroup('O programu');
		$frm->addSelect('section', 'Sekce', $sections)
			->setDefaultValue($this->item ? $this->item->section->id : null)
			->addRule(Form::FILLED);

		$frm->addText('name', 'Název')
			->setDefaultValue($this->item ? $this->item->name : null)
			->addRule(Form::FILLED);
		$frm->addText('lector', 'Přednášející / Pořádající')
			->setDefaultValue($this->item ? $this->item->lector : null)
			->addRule(Form::FILLED);

		$frm->addDateTimePicker('start', 'Začátek programu:')//DatePicker
			->setDefaultValue($this->item ? $this->item->start : null)
			->addRule(Form::FILLED);
		$frm->addDateTimePicker('end', 'Konec programu:')//DatePicker
			->setDefaultValue($this->item ? $this->item->end : null)
			->addRule(Form::FILLED);

		$frm->addText('capacity', 'Kapacita programu')
			->setDefaultValue($this->item ? $this->item->capacity : null)
			->addRule(Form::FILLED);

		$frm->addGroup('Podrobnosti');
		$frm->addTextArea('perex', 'Perex')
			->setDefaultValue($this->item ? $this->item->perex : null);
		$frm->addTextArea('location', 'Místo')
			->setDefaultValue($this->item ? $this->item->location : null);
		$frm->addTextArea('tools', 'Pomůcky a potřeby')
			->setDefaultValue($this->item ? $this->item->tools : null);

		$frm->addSubmit('send', 'Uložit')->setAttribute('class', 'btn btn-success btn-lg btn-block');
		$frm->onSuccess[] = [$this, 'frmEditSuccess'];

		return $frm;
	}


	/**
	 * Událost po pro uspěšně odeslaný formulář
	 *
	 * @param Form $frm
	 */
	public function frmEditSuccess(Form $frm)
	{
		$values = $frm->getValues();

		if (!$this->item)
		{
			$this->item = new Program();
			$this->em->persist($this->item);
		}

		foreach ($values as $key => $value)
		{
			if ($key == 'section')
			{
				$value = $value ? $this->sections->find($value) : null;
			}

			$this->item->$key = $value;
		}

		$this->em->flush();

		$this->flashMessage('Údaje úspěšně uloženy', 'success');
		$this->redirect('detail', $this->item->id);
	}


	/**
	 * Továrnna na komponentu tabulky přihlášených učastníků
	 * @return Datagrid
	 */
	protected function createComponentTblAttendees()
	{
		$grid = new Datagrid();

		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/templates/grid.layout.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Participants/grid.cols.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Program/attendees.cols.latte');

		$grid->addColumn('id', 'Id')->enableSort();
		$grid->addColumn('fullname', 'Jméno')->enableSort();
		$grid->addColumn('group', 'Skupina')->enableSort();

		$grid->addColumn('age', 'Věk')->enableSort();
		$grid->addColumn('contact', 'Kontakt')->enableSort();

		$grid->addColumn('confirmed', 'Přijede?')->enableSort();
		$grid->addColumn('paid', 'Zaplatil?')->enableSort();
		$grid->addColumn('arrived', 'Přijel?')->enableSort();
		$grid->addColumn('left', 'Odjel?')->enableSort();

		$grid->setFilterFormFactory(function ()
		{

			$form = new Container();
			$form->addText('id');
			$form->addText('fullname');
			$form->addText('group');
			$form->addText('age');
			$form->addText('contact');
			$form->addText('address');

			$form->addSelect('confirmed', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--')->setDefaultValue(true);
			$form->addSelect('paid', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('arrived', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('left', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');

			// these buttons are not compulsory
			$form->addSubmit('filter', 'Vyfiltrovat');
			$form->addSubmit('cancel', 'Zrušit');

			return $form;
		});

		$grid->setPagination(false);
		$grid->setDatasourceCallback(function ($filter, $sorting, Paginator $paginator = null)
		{

			$query = new ParticipantsQuery();
			$query->inProgram($this->item);

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
				elseif ($key == 'group')
				{
					$query->searchGroup($val);
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

				elseif ($key == 'confirmed')
				{
					$val ? $query->onlyConfirmed() : $query->onlyNotConfirmed();
				}
				elseif ($key == 'paid')
				{
					$val ? $query->onlyPaid() : $query->onlyNotPaid();
				}
				elseif ($key == 'arrived')
				{
					$val ? $query->onlyArrived() : $query->onlyNotArrived();
				}
				elseif ($key == 'left')
				{
					$val ? $query->onlyLeft() : $query->onlyNotLeft();
				}
			}

			// Pida do selectu zavislosti aby se pak nemuseli tahat solo
			$query->withGroup();

			$result = $this->repository->fetch($query);

			return $result;
		});

		return $grid;
	}

}



