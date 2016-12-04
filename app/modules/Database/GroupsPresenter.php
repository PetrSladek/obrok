<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Forms\IGroupFormFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Query\GroupsQuery;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Services\ImageService;
use Brabijan\Images\TImagePipe;
use Doctrine\ORM\AbstractQuery;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Http\IResponse;
use Nette\Security\Passwords;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;

/**
 * Class GroupsPresenter
 *
 * @author  psl <petr.sladek@webnode.com>
 * @persistent(tblGrid)
 */
class GroupsPresenter extends DatabaseBasePresenter
{

	use TImagePipe;

	/**
	 * @var ImageService
	 * @inject
	 */
	public $images;

	/**
	 * @var GroupsRepository
	 * @inject
	 */
	public $repository;

	/**
	 * @var GroupsRepository
	 * @inject
	 */
	public $groups;

	/**
	 * @var ParticipantsRepository
	 * @inject
	 */
	public $participants;

//    /** @var PaymentsRepository @inject */
//    public $payments;

	/**
	 * @var ServiceteamRepository
	 * @inject
	 */
	public $serviceteams;

	/**
	 * @var IGroupFormFactory
	 * @inject
	 */
	public $groupFormFactory;

	/**
	 * @var array|NULL
	 * @persistent
	 */
	public $filter = [];

	/**
	 * @var Group
	 */
	public $item;

	/**
	 * @var Participant
	 */
	public $participant;


	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();
		$this->acl->edit = $this->user->isInRole('groups-edit');
	}


	/**
	 * Přihlásit se do FrontEndu jako skupina
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

		$this->participant = $this->item->getAdministrators()->first();

		if (!$this->participant)
		{
			$this->flashMessage('Tato skupina nema administratora');
			$this->redirect('default');
		}

		$hash = Random::generate(22, '0-9A-Za-z./');
		$this->participant->quickLoginHash = Passwords::hash($hash);

		$this->em->persist($this->participant);
		$this->em->flush();

        $this->redirect(":Front:Login:as", $this->participant->getId(), $hash);
	}


	/**
	 * Továrna na komponentu tabulky
	 *
	 * @param $name
	 *
	 * @return Datagrid
	 */
	protected function createComponentTblGrid()
	{
		//@see http://addons.nettephp.com/cs/datagrid

		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/templates/grid.layout.latte');
		$grid->addCellsTemplate(__DIR__ . '/templates/Groups/grid.cols.latte');

		$grid->addColumn('varSymbol', 'ID / VS')->enableSort(/*Datagrid::ORDER_DESC*/);
		$grid->addColumn('name', 'Název')->enableSort();
		$grid->addColumn('region', 'Kraj')->enableSort();

		$grid->addColumn('participantsCount', 'Počet účastníků')->enableSort();

		$grid->addColumn('confirmed', 'Přijede?')->enableSort();
		$grid->addColumn('paid', 'Zaplatil?')->enableSort();
		$grid->addColumn('arrived', 'Přijel?')->enableSort();
		$grid->addColumn('left', 'Odjel?')->enableSort();

		$grid->setFilterFormFactory(function ()
		{
			$form = new Container();
			$form->addText('varSymbol');
			$form->addText('name');
			$form->addText('region');

			$form->addSelect('confirmed', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('paid', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('arrived', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');
			$form->addSelect('left', null, array(1 => 'Ano', 0 => 'Ne'))->setPrompt('--');

			$form->addSubmit('filter', 'Vyfiltrovat')->getControlPrototype()->class = 'btn btn-primary';
			$form->addSubmit('cancel', 'Zrušit')->getControlPrototype()->class = 'btn';

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
            if($sorting)
            {
                list($column, $order) = $sorting;

                if ($column == 'varSymbol')
                {
                    $column = 'g.id';
                }
                else if ($column == 'participantsCount')
                {
                    // TOOD tohle chce dořešit
                    $column = 'participantsCount';
//                    $column = 'g.id';
                }
                else
                {
                    $column = 'g.' . $column;
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
	 * @return GroupsQuery
	 */
	public function getFilteredQuery($filter)
	{
		$query = new GroupsQuery();
        $query->withParticipations();

		foreach ($filter as $key => $val)
		{
			if ($key == 'varSymbol')
			{
				$query->byVarSymbol($val);
			}
			elseif ($key == 'name')
			{
				$query->searchName($val);
			}
			elseif ($key == 'region')
			{
				$query->searchRegion($val);
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

		return $query;
	}


	/**
	 * Detail skupiny
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
	 * Vykreslení detailu skupiny
	 */
	public function renderDetail()
	{
		$link = $this->link('//:Front:Participants:Invitation:toGroup', $this->item->id, $this->item->getInvitationHash($this->config->hashKey));
		$this->template->invitationLink = $link;

		$this->template->activeParticipants = $this->item->getConfirmedParticipants();
		$this->template->canceledParticipants = $this->item->getUnconfirmedParticipants();
	}


	/**
	 * Editace skupiny
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
	 * Továrna na komponentu formuláře
	 *
	 * @return Form
	 */
	public function createComponentFrmEdit()
	{
//		return $this->groupFormFactory->create($this->item->id);


		$frm = new Form();
		$frm->setAjax();

		$frm->addText('name', 'Název skupiny')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setDefaultValue($this->item ? $this->item->name : null);
		$frm->addText('city', 'Město')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setDefaultValue($this->item ? $this->item->city : null);
		$frm->addTextarea('note', 'O skupině')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setDefaultValue($this->item ? $this->item->note : null);
		$frm->addTextarea('noteInternal', 'Interní poznámka')
			->setDefaultValue($this->item ? $this->item->noteInternal : null);

		$frm->addSelect('boss', 'Vedoucí skupiny (18+)', $this->item ? $this->item->getPossibleBosses($this->ageInDate) : [])
			->setDefaultValue($this->item && $this->item->getBoss() ? $this->item->getBoss()->id : null)
			->setPrompt('- Vyberte vedoucího sk. -');

//        $frm->addText('paidFor','Zaplaceno za')
//            ->setDefaultValue($this->item ? $this->item->paidFor : null)
//            ->addRule(Form::INTEGER, 'Musí být celé číslo')
//            ->setDefaultValue(0);

		$frm->addCroppie('avatar', 'Obrázek / znak skupiny')
			->setImageUrl($this->item && $this->item->getAvatar() ? $this->imageService->getImageUrl($this->item->getAvatar()) : null)
			->setEmptyImageUrl($this->imageService->getImageUrl('avatar_group.jpg'))
			->setDefaultValue($this->item && $this->item->getAvatarCrop() ? $this->item->getAvatarCrop() : null);


		$frm->addSubmit('send', 'Uložit')->setAttribute('class', 'btn btn-success btn-lg btn-block');
		$frm->onSuccess[] = [$this, 'frmEditSuccess'];

		return $frm;
	}


	/**
	 * Akce při uspěšnéím odeslání formuláře
	 *
	 * @param Form $frm
	 */
	public function frmEditSuccess($frm)
	{
		$values = $frm->getValues();

		if (!$this->item)
		{
			$this->item = new Group();
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
			if ($key == 'boss')
			{
				$value = $value ? $this->participants->find($value) : null;
			}

			$this->item->$key = $value;
		}

		$this->em->flush();

		$this->flashMessage('Údaje úspěšně uloženy', 'success');
		$this->redirect('detail', $this->item->id);
	}


	/**
	 * Továrna na komponentu tabulky plateb
	 * @return Datagrid
	 */
	public function createComponentTblPayments()
	{
		$grid = new Datagrid();
		$grid->setRowPrimaryKey('id');
		$grid->addCellsTemplate(__DIR__ . '/../templates/grid.layout.latte');
//        $grid->addCellsTemplate(__DIR__.'/../templates/Payments/grid.cols.latte');

		$grid->addColumn('id', 'ID')->enableSort();
		$grid->addColumn('date', 'Datum')->enableSort();
		$grid->addColumn('varSymbol', 'Var.Symbol')->enableSort();
//        $grid->addColumn('constSymbol', 'Konst.Symbol')->enableSort();
//        $grid->addColumn('specificSymbol', 'Spec.Symbol')->enableSort();

		$grid->addColumn('amount', 'Částka')->enableSort();
		$grid->addColumn('fromBankAccount', 'Proti účet')->enableSort();

		$grid->addColumn('fromName', 'Majitel protiúčtu')->enableSort();
		$grid->addColumn('description', 'Popis')->enableSort();

//        $grid->addColumn('tshirtSize', 'Tričko')->enableSort();

		$grid->setFilterFormFactory(function ()
		{
			$frm = new Container();
			$frm->addText('id')
				->addCondition(Form::FILLED)
				->addRule(Form::INTEGER);
			$frm->addText('date');
			$frm->addText('varSymbol')
				->addCondition(Form::FILLED)
				->addRule(Form::INTEGER);

			$frm->addText('amount')
				->addCondition(Form::FLOAT)
				->addRule(Form::INTEGER);

			$frm->addText('fromBankAccount');
			$frm->addText('fromName');
			$frm->addText('description');

			// set your own fileds, inputs

			// these buttons are not compulsory
			$frm->addSubmit('filter', 'Vyfiltrovat');
			$frm->addSubmit('cancel', 'Zrušit');

//            $form->setDefaults($this->filter);

			return $frm;
		});

//        $grid->setPagination($this->gridItemsPerPage, function($filter) {
//                $count = $this->payments->findFiltered($filter)->count();
//                return $count;
//        });
		$grid->setDatasourceCallback(function ($filter, $order, Paginator $paginator = null)
		{ // filter pouzivam ze svyho externiho formu

//            $filter['group'] = $this->item->id;
//
//            $collection = $this->payments->findFiltered(
//                $filter,
//                $order ?: ['id', 'DESC'],
//                $paginator ? $paginator->itemsPerPage : null,
//                $paginator ? $paginator->offset : null
//            );

//            return $collection;

			return [];
		});

		return $grid;
	}


	/**
	 * Ajaxová inline editace formuláře
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

		$this->em->persist($this->item);
		$this->em->flush();

		if ($snippet == 'boss')
//        {
		{
			$this->redrawControl('phone');
		}
//        } elseif($snippet == 'paidFor') {
//            $this->redrawControl('flags');
//            $this->redrawControl('participants');
//        }

	}


	/**
	 * Akce změna stavu
	 *
	 * @param string $status
	 * @param bool   $value
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleStatus($status = 'confirmed', $value = true)
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

			// nastavi vsem aktivnim ucastnikum stav
			foreach ($this->item->getConfirmedParticipants() as $participant)
			{
				// zavola metody setConfirmed, setPaid,...
				$method = "set" . ucfirst($status);
				$participant->$method($value);
			}

			$this->em->flush();
		}
		catch (\InvalidArgumentException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->redrawControl('flags');
		$this->redrawControl('participants');
		$this->redrawControl('canceled-participants');

		if (!$this->isAjax())
		{
			$this->redirect('this');
		}
	}


	/**
	 * Akce změna stavu učastníka
	 *
	 * @param        $participantId
	 * @param string $status
	 * @param bool   $value
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleParticipantStatus($participantId, $status = 'confirmed', $value = true)
	{
		if (!$this->acl->edit)
		{
			$this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);
		}

		$this->participant = $this->participants->find($participantId);
		if (!$this->participant)
		{
			$this->error('Účastník neexistuje');
		}

		try
		{
			if (!in_array($status, ['confirmed', 'paid', 'arrived', 'left']))
			{
				throw new \InvalidArgumentException("Wrong status name");
			}

			// zavola metody setConfirmed, setPaid,...
			$method = "set" . ucfirst($status);
			$this->participant->$method($value);

//            $this->em->persist($this->participant);
			$this->em->flush();
		}
		catch (\InvalidArgumentException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->redrawControl('flags');
		$this->redrawControl('participants');
		$this->redrawControl('canceled-participants');

		if (!$this->isAjax())
		{
			$this->redirect('this');
		}
	}


	/**
	 * Vykreslení PDF potvrzení příjezdu
	 *
	 * @param null $id
	 */
	public function renderConfirmations($id = null)
	{

		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '1024M');

		$conditions = ['confirmed' => 1];
		if ($id)
		{
			$conditions['id'] = $id;
		}

		/** @var ICollection $collection */
		$collection = $this->repository->findBy($conditions);

		$template = $this->createTemplate();
		$template->setFile($this->context->expand("%appDir%/ModuleDatabase/templates/Groups/confirmations.latte"));

		$list = $collection->fetchAll();
		$template->list = $list;

//        $this->template->list = $list;
		$this->sendResponse(new PdfResponse($template));
	}


	/**
	 * Vykreslené PDF potvrzení platby
	 *
	 * @param null $id
	 */
	public function renderPayment($id = null)
	{

		ini_set('max_execution_time', 0);
		ini_set('memory_limit', '1024M');

		$conditions = ['confirmed' => 1];
		if ($id)
		{
			$conditions['id'] = $id;
		}

		/** @var ICollection $collection */
		$collection = $this->repository->findBy($conditions);

		$template = $this->createTemplate();
		$template->setFile($this->context->expand("%appDir%/ModuleDatabase/templates/Groups/payment.latte"));

		$list = $collection->fetchAll();
		$template->list = $list;
		//$this->template->list = $list;

		$pdf = new PdfResponse($template);
		$pdf->pageFormat = 'A5';
//		$pdf->pageOrientaion = PdfResponse::ORIENTATION_LANDSCAPE;
		$this->sendResponse($pdf);
	}


	/**
	 * Akce pro vygooglení kraje k městu
	 * @throws \Nette\Application\AbortException
	 */
	public function actionSetRegionToGroups()
	{
		echo '<pre>';

		/** @var Group[] $groups */
		$groups = $this->groups->findAll();
		foreach ($groups as $group)
		{

            if ($group->region)
            {
                continue;
            }

			$data = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=" . urlencode($group->city . ' ,Česká republika') . "&sensor=false&language=cs");
			$data = json_decode($data);

			if (empty($data->results[0]->address_components))
			{
				continue;
			}
			$region = null;
			foreach ($data->results[0]->address_components as $row)
			{
				if ($row->types[0] == 'administrative_area_level_1' && $row->types[1] == 'political')
				{
					$region = $row->long_name;
					break;
				}
			}
			$group->region = $region;
			echo "{$group->city} : {$group->region}\n";

		}
        $this->em->flush();
		echo "Hotovo\n";
		$this->terminate();
	}

}



