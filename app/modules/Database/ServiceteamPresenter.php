<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Serviceteam;
use App\Query\ServiceteamQuery;
use App\Model\Repositories\JobsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Model\Repositories\TeamsRepository;
use App\Model\Repositories\WorkgroupsRepository;
use App\Services\ImageService;

use Brabijan\Images\TImagePipe;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Http\IResponse;
use Nette\Security\Passwords;

use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;

/**
 * Class ServiceteamPresenter
 *
 * @author  psl <petr.sladek@webnode.com>
 */
class ServiceteamPresenter extends DatabaseBasePresenter
{

	/** @var ServiceteamRepository @inject */
	public $repository;

	/** @var TeamsRepository @inject */
	public $teams;

	/** @var WorkgroupsRepository @inject */
	public $workgroups;

	/** @var JobsRepository @inject */
	public $jobs;

	/** @var ImageService @inject */
	public $images;

	/** @var array|NULL
	 * @persistent
	 */
	public $filter = [];

	/** @var Serviceteam */
	public $item;


	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();
		$this->acl->edit = $this->user->isInRole('serviceteam-edit');
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
		$grid->addCellsTemplate(__DIR__ . '/templates/Serviceteam/grid.cols.latte');

		$grid->addColumn('varSymbol', 'ID / VS')->enableSort();
		$grid->addColumn('fullname', 'Jméno')->enableSort();
		$grid->addColumn('address', 'Město')->enableSort();
		$grid->addColumn('age', 'Věk')->enableSort();
		$grid->addColumn('contact', 'Kontakt')->enableSort();

		$grid->addColumn('team', 'Tým')->enableSort();
		$grid->addColumn('group', 'Skupina')->enableSort();

		$grid->addColumn('confirmed', 'Přijede?')->enableSort();
		$grid->addColumn('paid', 'Zaplatil?')->enableSort();
		$grid->addColumn('arrived', 'Přijel?')->enableSort();
		$grid->addColumn('left', 'Odjel?')->enableSort();

//        $grid->addColumn('tshirtSize', 'Tričko')->enableSort();

		$grid->setFilterFormFactory(function ()
		{
			$form = new Container();
			$form->addText('varSymbol');
			$form->addText('fullname');
			$form->addText('address');
			$form->addText('age')
				 ->addCondition(Form::FILLED)
				 ->addRule(Form::INTEGER);
			$form->addText('contact');

			$form->addMultiSelect('team', null, $this->teams->findPairs("name"));
			$form->addText('group');

			$form->addSelect('confirmed', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--')->setDefaultValue(true);
			$form->addSelect('paid', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('arrived', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('left', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');

			$form->addSubmit('filter', 'Vyfiltrovat');
			$form->addSubmit('cancel', 'Zrušit');

//            $form->setDefaults($this->filter);
			return $form;
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

			return $result->toArray();
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
	 * @return ServiceteamQuery
	 */
	public function getFilteredQuery($filter)
	{
		$query = new ServiceteamQuery();

		foreach ($filter as $key => $val)
		{
			if ($key == 'varSymbol')
			{
				$query->byVarSymbol($val);
			}
			elseif ($key == 'fullname')
			{
				$query->searchFullname($val);
			}
			elseif ($key == 'address')
			{
				$query->searchAddress($val);
			}
			elseif ($key == 'age')
			{
				$query->byAge($val, $this->ageInDate);
			}
			elseif ($key == 'contact')
			{
				$query->searchContact($val);
			}
			elseif ($key == 'team')
			{
				$query->inTeams($val);
			}
			elseif ($key == 'group')
			{
				$query->searchWorkgroupOrJob($val);
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
		$query->withJob();
		$query->withTeam();
		$query->withWorkgroup();

		return $query;
	}


	/**
	 * Detail servisáka
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
	 * Editace servisáka
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
	 * Továrna na editační formulář
	 * @return Form
	 */
	public function createComponentFrmEdit()
	{
		$frm = new Form();
		$frm->setAjax();

		$frm->addGroup('Osobní informace');
		$frm->addText('firstName', 'Jméno')
			->setDefaultValue($this->item ? $this->item->firstName : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat jméno');
		$frm->addText('lastName', 'Příjmení')
			->setDefaultValue($this->item ? $this->item->lastName : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Přímení');
		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->item ? $this->item->nickName : null);
		$frm->addDatePicker('birthdate', 'Datum narození:')//DatePicker
			->setDefaultValue($this->item ? $this->item->birthdate : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
			->setAttribute('title', 'Tvoje Datum narození ve formátu dd.mm.yyyy');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->item ? $this->item->addressCity : null)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
			->setAttribute('title', 'Město, kde aktuálně bydlí nebo skautuje');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('email', 'E-mail')
			->setDefaultValue($this->item ? $this->item->email : null)
			->setEmptyValue('@')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
			->addRule(Form::EMAIL, 'E-mailová adresa není platná')
			->setAttribute('title', 'E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat.  Budou Ti chodit informace atd..');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->item ? $this->item->phone : null)
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			->setAttribute('title', 'Mobilní telefon, na kterém budeš k zastižení během celé akce');

		$frm->addGroup('Zařazení');
		$frm->addSelect('team', 'Spadá pod tým', $this->teams->findPairs("name"))
			->setDefaultValue($this->item && $this->item->team ? $this->item->team->id : null)
			->setPrompt('- nezařazen -');

		$frm->addTypeahead('workgroup', 'Patří do prac.skupiny', function ($query)  {
            return $this->workgroups->findPairs(['name like' => "%{$query}%"], 'name');
		})->setDefaultValue($this->item && $this->item->workgroup ? $this->item->workgroup->name : null);

		$frm->addTypeahead('job', 'Pozice', function ($query) {
			return $this->jobs->findPairs(['name like' => "%{$query}%"], 'name');
		})->setDefaultValue($this->item && $this->item->job ? $this->item->job->name : null);

//        $frm->addCheckbox('replacer','Náhradník?');
		$frm->addGroup('Zdravotní omezení');
		$frm->addTextarea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->item ? $this->item->health : null);

		$frm->addGroup('Poznámky');
		$frm->addTextArea('experience', 'Zkušenosti / Dovednosti')
			->setDefaultValue($this->item ? $this->item->experience : null)
			->addFilter('trim');
		$frm->addTextArea('note', 'Poznámka při registraci / Omezení (diety)')
			->setDefaultValue($this->item ? $this->item->note : null)
			->addFilter('trim');
		$frm->addTextArea('noteInternal', 'Interní poznámka')
			->setDefaultValue($this->item ? $this->item->noteInternal : null)
			->addFilter('trim');

		$frm->addCroppie('avatar', 'Fotka')
			->setImageUrl($this->item && $this->item->getAvatar() ? $this->imageService->getImageUrl($this->item->getAvatar()) : null)
			->setDefaultValue($this->item && $this->item->getAvatarCrop() ? $this->item->getAvatarCrop() : null);

		$frm->addGroup('Ostatní');
		$frm->addCheckbox('helpPreparation', 'Má zájem pomoct s přípravami')
			->setDefaultValue($this->item ? $this->item->helpPreparation : null);
		$frm->addSelect('tshirtSize', 'Velikost trička', Serviceteam::$tShirtSizes)
			->setPrompt('- vyberte velikost -')
			->setDefaultValue($this->item ? $this->item->tshirtSize : null)
			->setRequired();

		$frm->addGroup('Přihlášení');
		$frm->addText('skautisPersonId', 'Skautis PersonID')
			->addRule(Form::INTEGER)
			->setDefaultValue($this->item ? $this->item->skautisPersonId : null);

		$frm->addSubmit('send', 'Uložit')->setAttribute('class', 'btn btn-success btn-lg btn-block');
		$frm->onSuccess[] = [$this, 'frmEditSuccess'];

		return $frm;
	}


	/**
	 * Událost po uspěšném odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmEditSuccess(Form $frm)
	{
		$values = $frm->getValues();

		if (!$this->item)
		{
			$this->item = new Serviceteam();
			$this->em->persist($this->item);
		}

		/** @var \Croppie $avatar */
		$avatar = $values->avatar;
		unset($values->avatar);

		if ($avatar)
		{
			if ($image = $avatar->getFileUpload())
			{
				$filename = $this->imageService->upload($image);
				$this->item->setAvatar($filename);
			}

			$this->item->setAvatarCrop($avatar->getCrop());
		}
		else
		{
			$this->item->removeAvatar();
		}


		foreach ($values as $key => $value)
		{
			if ($key == 'workgroup')
			{
				$value = $value ? $this->workgroups->checkByName($value) : null;
			}
			elseif ($key == 'job')
			{
				$value = $value ? $this->jobs->checkByName($value) : null;
			}
			elseif ($key == 'team')
			{
				$value = $value ? $this->teams->find($value) : null;
			}

			$this->item->$key = $value;
		}

		$this->em->flush();

		$this->flashMessage('Údaje úspěšně uloženy', 'success');
		$this->redirect('detail', $this->item->id);
	}


	/**
	 * Ajaxová inline editace
	 *
	 * @param Form  $frm
	 * @param array $data
	 * @param       $snippet
	 *
	 * @throws \Exception
	 */
	public function ajaxEdit(Form $frm, array $data, $snippet)
	{
		// Akce
		switch ($snippet)
		{
			case 'workgroup':
				$val = $frm['workgroup']->getValue();
				$this->item->workgroup = $this->workgroups->checkByName($val);
				break;
			case 'job':
				$val = $frm['job']->getValue();
				$this->item->job = $this->jobs->checkByName($val);
				break;

			case 'team':
				$val = $frm['team']->getValue();
				$this->item->team = $val ? $this->teams->find($val) : null;
				break;
			default:
				foreach ($data as $key => $val)
				{
					/** @var BaseControl $control */
					$control = $frm->getComponent($key);
					if (!$control->getRules()->validate())
					{
						throw new \Exception(current($control->getErrors()), 300);
					}
					$this->item->$key = $control->getValue();
				}
				break;
		}

		$this->em->flush();
	}


	/**
	 * Změna statusu
	 *
	 * @param string $status confirmed | paid | arrived | left
	 * @param bool   $value
	 *
	 * @param int|null   $id
	 *
	 * @throws \Exception
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleStatus($status, $value = true, $id = null)
	{
		if (!$this->acl->edit)
		{
			$this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);
		}

		try
		{
			if (!in_array($status, ['confirmed', 'paid', 'arrived', 'left']))
			{
				throw new \InvalidArgumentException("Wrong status name");
			}

			if($id)
			{
				$this->item = $this->repository->find($id);
			}

			// zavola metody setConfirmed, setPaid,...
			$method = "set" . ucfirst($status);
			$this->item->$method($value);

			$this->em->flush();
		}
		catch (\InvalidArgumentException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->redrawControl('flags');
		$this->redrawControl('tblGrid');

		if (!$this->isAjax())
		{
			$this->redirect('this');
		}
	}


	/**
	 * Přihlásit se na FronteEnd jako servisák
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

		$this->redirect(":Serviceteam:Login:as", $id, $hash);
	}


	/**
	 * Vykreslí PDF s platbami
	 *
	 * @param null $id
	 */
	public function renderPayment($id = null)
	{

		ini_set('memory_limit', '1024M');

		$list = $this->repository->findBy(['confirmed' => true]);;

		$template = $this->createTemplate()->setFile(APP_DIR . '/ModuleDatabase/templates/Serviceteam/payment.latte');
		$template->list = $list;

		$pdf = new PdfResponse($template);
		$pdf->pageFormat = 'A5';
//		$pdf->pageOrientaion = PdfResponse::ORIENTATION_LANDSCAPE;
		$this->sendResponse($pdf);
	}


//    /**
//     * Vrátí data pro napovídání typeahead inputu
//     * @param $table
//     */
//	public function actionTypeahead($table)
//    {
//        $result = [];
//        if ($table == 'workgroups')
//        {
//            $result = $this->workgroups->findPairs([], 'name');
//        }
//        elseif ($table == 'jobs')
//        {
//            $result = $this->jobs->findPairs([], 'name');
//        }
//
//        $this->sendJson(array_values($result));
//    }
}



