<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Program;
use App\Query\ParticipantsQuery;
use App\Query\ProgramsQuery;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
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
 * Class ParticipantsPresenter
 * @package App\Module\Database\Presenters
 * @author  psl <petr.sladek@webnode.com>
 * @persistent(tblGrid)
 */
class ParticipantsPresenter extends DatabaseBasePresenter
{

	/** @var ParticipantsRepository @inject */
	public $repository;

	/** @var GroupsRepository @inject */
	public $groups;

	/** @var ProgramsRepository @inject */
	public $programs;

	/** @var ProgramsSectionsRepository @inject */
	public $sections;

	/** @var Participant */
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
		$grid->addColumn('group', 'Skupina')->enableSort();

		$grid->addColumn('age', 'Věk')->enableSort();
		$grid->addColumn('contact', 'Kontakt')->enableSort();
		$grid->addColumn('address', 'Adresa')->enableSort();
		$grid->addColumn('wantHandbook', 'HB')->enableSort();

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

		$grid->addGlobalAction('export', 'Export', function (array $ids, Datagrid $grid)
		{
			$this->redirect('export', [
				'ids' => array_values($ids),
				'filename' => 'export-ucastnici-' . date('YmdHis') . '.csv'
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
	 * @param $filter
	 *
	 * @return ParticipantsQuery
	 */
	public function getFilteredQuery($filter)
	{
		$query = new ParticipantsQuery();

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

			elseif ($key == 'confirmed' && $val !== null)
			{
				$val ? $query->onlyConfirmed() : $query->onlyNotConfirmed();
			}
			elseif ($key == 'paid' && $val !== null)
			{
				$val ? $query->onlyPaid() : $query->onlyNotPaid();
			}
			elseif ($key == 'arrived' && $val !== null)
			{
				$val ? $query->onlyArrived() : $query->onlyNotArrived();
			}
			elseif ($key == 'left' && $val !== null)
			{
				$val ? $query->onlyLeft() : $query->onlyNotLeft();
			}
		}

		// Pida do selectu zavislosti aby se pak nemuseli tahat solo
		$query->withGroup();

		return $query;
	}


	/**
	 * Detail uřastníka
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
	}


	/**
	 * Příkaz smazání programu učastníkovi
	 *
	 * @param $idProgram
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleDeleteProgram($idProgram)
	{

		$program = $this->programs->find($idProgram);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		$this->item->unattendeeProgram($program);
		$this->em->flush();

		$this->flashMessage('Program úspěšně odhlášen.', 'success');
		if ($this->isAjax())
		{
			$this->redrawControl('programs');
			$this->redrawControl('flashes');
		}
		else
		{
			$this->redirect('this');
		}

	}


	/**
	 * Přidání programu učastníkovi
	 *
	 * @param $idProgram
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleAddProgram($idProgram)
	{
		/** @var Program $program */
		$program = $this->programs->find($idProgram);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		try
		{
			// zaregistrujeme program klidne i přes kapacitu
			$this->item->attendeeProgramOverCapacity($program);

			$this->em->flush();
			$this->flashMessage('Program úspěšně zaregistrovan.', 'success');

		}
		catch (InvalidStateException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		if ($this->isAjax())
		{
			$this->redrawControl('programs');
			$this->redrawControl('flashes');
		}
		else
		{
			$this->redirect('this');
		}

	}


	/**
	 * Povolí uživateli registraci programu
	 */
	public function handleOpenProgramRegistration()
	{
		$this->item->openProgramRegistration();
		$this->em->flush($this->item);

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Zavře uživateli registraci programu
	 */
	public function handleCloseProgramRegistration()
	{
		$this->item->closeProgramRegistration();
		$this->em->flush($this->item);

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Vykreslení šavlony detailu učastníka
	 *
	 * @param int $id
	 */
	public function renderDetail($id)
	{
		$this->template->item = $this->item;
	}


	/**
	 * Editace učastníka
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
	 * Továrna na komponentu formuláře editace
	 *
	 * @return Form
	 */
	public function createComponentFrmEdit()
	{

		$frm = new Form();
		$frm->setAjax();

		$frm->addGroup('Skupina');

		$frm->addSelect('group', 'Skupina do které uživatel patří', $this->groups->findPairs("name"))
			->setDefaultValue($this->item ? $this->item->group->getId() : $this->getParameter('toGroup'))
			->setPrompt('- Vyberte skupinu -')
			->setRequired();

		$frm->addGroup('Osobní informace');

		$frm->addText('firstName', 'Jméno')
			->setDefaultValue($this->item ? $this->item->firstName : null)
			->setRequired();
		$frm->addText('lastName', 'Příjmení')
			->setDefaultValue($this->item ? $this->item->lastName : null)
			->setRequired();
		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->item ? $this->item->nickName : null);

		$frm->addDatepicker('birthdate', 'Datum narození:')
			->setDefaultValue($this->item ? $this->item->birthdate->format('j.n.Y') : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
            ->addRule(Form::RANGE, 'Podle data narození Vám 22.5.2019 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('22.5.2019')->modify('-15 years')))
            ->addRule(Form::RANGE, 'Podle data narození Vám 26.5.2019 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('27.5.2019')->modify('-25 years'), null));

//            ->addRule(callback('Participant','validateAge'), 'Věk účastníka Obroku 2015 musí být od 15 do 24 let');

		$frm->addRadioList('gender', 'Pohlaví', array(Person::GENDER_MALE => 'muž', Person::GENDER_FEMALE => 'žena'))
			->setDefaultValue($this->item ? $this->item->gender : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Trvalé bydliště');
		$frm->addText('addressStreet', 'Ulice a čp.')
			->setDefaultValue($this->item ? $this->item->addressStreet : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->item ? $this->item->addressCity : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressPostcode', 'PSČ')
			->setDefaultValue($this->item ? $this->item->addressPostcode : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('email', 'E-mail')
			->setDefaultValue($this->item ? $this->item->email : null)
			->setEmptyValue('@')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
			->addRule(Form::EMAIL, 'E-mailová adresa není platná')
			->setAttribute('title', 'E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat. Budou Ti chodit informace atd..')
			->setAttribute('data-placement', 'right');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->item ? $this->item->phone : null)
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			->setAttribute('title', 'Mobilní telefon, na kterém budeš k zastižení během celé akce')
			->setAttribute('data-placement', 'right');

		$frm->addGroup('Zdravotní omezení');
		$frm->addTextArea('health', 'Zdravotní omezení a alergie')
            ->setOption('description', Html::el('')->setHtml('Pokud máte nějaký handicap a potřebujete více informací, může se kdykoliv ozvat zde: Ladislava Blažková <a href="mailto:ladkablazkova@gmail.com">ladkablazkova@gmail.com</a> | +420 728 120 498'))
			->setDefaultValue($this->item ? $this->item->health : null);

		$frm->addCheckbox('admin', 'Administrátor skupiny')
			->setDefaultValue($this->item ? $this->item->isAdmin() : null);

		$frm->addTextArea('noteInternal', 'Interní poznámka')
			->setDefaultValue($this->item ? $this->item->noteInternal : null);

		$frm->addGroup('Přihlášení');
//		$frm->addText('skautisPersonId', 'Skautis PersonID')
//			->setRequired(false)
//			->addRule(Form::INTEGER)
//			->setDefaultValue($this->item ? $this->item->skautisPersonId : null);

		$frm->addText('skautisUserId', 'Skautis UserID')
			->setRequired(false)
			->addRule(Form::INTEGER)
			->setDefaultValue($this->item ? $this->item->skautisUserId : null);

		$frm->addSubmit('send', 'Uložit')->setAttribute('class', 'btn btn-success btn-lg btn-block');

		$frm->onSuccess[] = [$this, 'frmEditSubmitted'];

		return $frm;
	}


	/**
	 * Akce po odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmEditSubmitted(Form $frm)
	{
		$values = $frm->getValues();

		if (!$this->item)
		{
			$this->item = new Participant();
			$this->em->persist($this->item);
		}

		foreach ($values as $key => $value)
		{
			if ($key == 'group')
			{
				$value = $value ? $this->groups->find($value) : null;
			}

			$this->item->$key = $value;
		}

		$this->em->flush();

		$this->flashMessage('Údaje úspěšně uloženy', 'success');
		$this->redirect('detail', $this->item->getId());

	}


	/**
	 * Přihlásit se na FrontEnd jako účastník
	 *
	 * @param $id
	 *
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
		$this->item->quickLoginHash = Passwords::hash($hash);

		$this->em->persist($this->item);
		$this->em->flush();

		$this->redirect(":Front:Login:as", $id, $hash);
	}


	/**
	 * Továrna na komponentu tabulky programů
	 * @return Datagrid
	 */
	public function createComponentTblPrograms()
	{
		$grid = new Datagrid();

		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/templates/grid.layout.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Program/grid.cols.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Participants/programs.cols.latte');

		$grid->addColumn('id', 'ID');
		$grid->addColumn('section', 'Sekce');
		$grid->addColumn('time', 'Den a čas');
		$grid->addColumn('name', 'Název');
		$grid->addColumn('capacity', 'Obsazeno');//->enableSort();
		$grid->addColumn('registration', '');

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

		$grid->setPagination(false);
		$grid->setDataSourceCallback(function ($filter, $sorting, Paginator $paginator = null)
		{

			$query = new ProgramsQuery();
			$query->withoutKrinspiro();

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

			$result = $this->repository->fetch($query);

			return $result;
		});

		return $grid;
	}


	/**
	 * Nastaví LAT a LNG k ucastnikum kteri ji jeste nemaji
	 */
	public function actionSetLocationToParticipant()
	{
		echo '<pre>';

		/** @var Participant[] $participants */
		$participants = $this->participants->findBy(['location_lat' => null, 'location_lng' => null], null, 100);
		foreach ($participants as $participant)
		{
			if ($participant->getLocation())
			{
				continue;
			}

			$address = (string) $participant->getAddress();

			$data = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($address) . "&sensor=false&language=cs");
			$data = json_decode($data);

			if (isset($data->results[0]->geometry))
			{
				$geometry = $data->results[0]->geometry;
				$lat = $geometry->location->lat;
				$lng = $geometry->location->lng;

				$participant->setLocation($lat, $lng);

				echo "{$participant->getFullname()} : {$lat},{$lng}\n";
			}
		}

		$this->em->flush();
		echo "Hotovo\n";
		$this->terminate();
	}




}



