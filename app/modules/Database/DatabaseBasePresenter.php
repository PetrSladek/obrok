<?php

namespace App\Module\Database\Presenters;

use App\BasePresenter;
use App\Forms\Form;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ServiceteamRepository;
use Kdyby\Doctrine\EntityDao;
use Kdyby\Doctrine\EntityManager;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\Controls\Button;
use Nette\Forms\Controls\Checkbox;
use Nette\Forms\Controls\CheckboxList;
use Nette\Forms\Controls\MultiSelectBox;
use Nette\Forms\Controls\RadioList;
use Nette\Forms\Controls\SelectBox;
use Nette\Forms\Controls\TextBase;
use Nette\Utils\ArrayHash;
use Nette\Utils\Paginator;
use Nextras\Datagrid\Datagrid;

/**
 * Administrace BasePresenter
 *
 * @author     Petr /Peggy/ Sládek
 * @package    PeggyCMS
 */
abstract class DatabaseBasePresenter extends \App\Module\Base\Presenters\BasePresenter
{

	/** @var Serviceteam */
	public $me;

	/** @var ServiceteamRepository @inject */
	public $serviceteams;

    /**
     * Výchozí počet záznamů na stránku
     *
     * @var int
     */
	public $gridItemsPerPage = 100;

	/**
	 * @var ArrayHash
	 */
	public $acl;


	/**
	 * @inheritdoc
	 */
	public function startup()
	{
		parent::startup();

		$this->acl = new ArrayHash();
		$this->template->acl = $this->acl;

		$this->checkAllowed(array('database')); // je Servisák a má přístup k databázi?

		/** @var Serviceteam */
		$me = $this->serviceteams->find($this->getUser()->getId());
		if (!$me)
		{
			$this->getUser()->logout(true);
			$this->redirect("Login:", array('back' => $this->storeRequest()));
		}
		$this->me = $me;
		$this->template->me = $this->me;

		if ($this->isAjax())
		{
			$this->redrawControl('title');
			$this->redrawControl('navbar');
			$this->redrawControl('content');
		}
	}


	/**
	 * Vykreslení výchozí šablony
	 */
	public function renderDefault()
	{

		/** @var Datagrid $grid */
		if ($grid = $this->getComponent('tblGrid', false))
		{
			/** @var Template $tpl */
			$tpl = $grid->getTemplate();

			$tpl->acl = $this->acl;
			$tpl->ageInDate = $this->ageInDate;
		}
	}


	/**
	 * Kontrola oprávnění
	 *
	 * @param $role
	 */
	public function checkAllowed($role)
	{
		$role = is_scalar($role) ? array($role) : $role;
		$allowed = true;
		$allowed &= $this->getUser()->isLoggedIn();
		foreach ($role as $r)
		{
			$allowed &= $this->getUser()->isInRole($r);
		}

		if (!$allowed)
		{
			$this->getUser()->logout();

			$this->flashMessage('Nemáte oprávnění pro vstup. Musíte se přihlásit');

			$this->redirect('Login:', array('back' => $this->storeRequest()));
		}
	}


	/**
	 * Akce pro uložení ajaxové inline editace
	 *
	 * @param       $id
	 * @param array $data
	 * @param       $snippet
	 */
	public function handleAjaxEdit($id, array $data, $snippet)
	{

		$this->payload->status = 200;
		try
		{
			if (!$this->acl->edit)
			{
				throw new \Exception('Nemáte oprávnění', 300);
			}

			$frm = $this->getComponent('frmEdit');
			if (!$frm)
			{
				throw new \Exception("V tomto presenteru neexistuje frmEdit form");
			}

			// prazdny pole z POSTu na null
			$data = array_map(function ($item)
			{
				return $item === '' ? null : $item;
			}, $data);

			/** @var $frm Form */
			$frm->setValues($data);
			$this->ajaxEdit($frm, $data, $snippet);

			$this->redrawControl($snippet);

		}
		catch (\Exception $e)
		{
			$this->payload->status = $e->getCode();
			$this->payload->message = $e->getMessage();
		}

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

	}


	/**
	 * Akce pro ajaxový refresh stránky (jednoho nebo všech snippetů)
	 *
	 * @param null $snippet
	 */
	public function handleRefresh($snippet = null)
	{
		$this->redrawControl($snippet);
	}


	/**
	 * Pokud jde o ajax překreslí stránku (vrátí snippety)
	 * jinak přesměruje na this
	 */
	public function redrawOrRedirect()
	{
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
	 * @return Form
	 */
	public function createComponentFrmSearch()
	{
		$frm = new Form();

		$frm->addText('query', 'Vyhledat ...')
			->setDefaultValue($this->getParameter('query'))
			->setRequired();

		$frm->addSubmit('send', 'Odeslat');

		$frm->onSuccess[] = [$this, 'frmSearchSubmitted'];

		return $frm;
	}

	/**
	 * @param Form $frm
	 */
	public function frmSearchSubmitted(Form $frm)
	{
		$values = $frm->getValues();

		$this->redirect('Search:', ['query' => $values->query]);
	}




}


