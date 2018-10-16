<?php

namespace App\Module\Database\Presenters;

use App\BasePresenter;
use App\Forms\Form;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Phone;
use App\Model\Repositories\ServiceteamRepository;
use Doctrine\ORM\AbstractQuery;
use League\Csv\CharsetConverter;
use League\Csv\Writer;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Controls\BaseControl;
use Nette\Utils\ArrayHash;
use Nette\Utils\Image;
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


	/**
	 * Formátovač pro exporty data tables
	 *
	 * @param array $data
	 * @return array
	 * @internal param array $row
	 */
	public function exportFormatter(array $data)
	{
		// vyfiltrujeme klice ktere nechceme videt
		$row = array_filter($data, function ($key) {
			return !in_array($key, ['avatarCrop']); // klice ktere nechceme exportovat
		}, ARRAY_FILTER_USE_KEY);

		if (array_keys($row) === array_values($row))
		{
			return $row;
		}

		return array_map(function ($value, $key) use ($data)
		{
			if ($key === 'phone')
			{
				return (string) (new Phone($value));
			}
			elseif ($key === 'avatar')
			{
				$baseUrl = rtrim($this->getHttpRequest()->getUrl()->getBaseUrl(), '/');

				if (!$value)
				{
					$value = (isset($data['gender']) && $data['gender'] === Person::GENDER_MALE
						? 'avatar_boy.jpg'
						: 'avatar_girl.jpg');
				}

				return $baseUrl . $this->imageService->getImageUrl($value, 800, 800, Image::EXACT, $data['avatarCrop'] ?? null);
			}
			else if ($value instanceof \DateTimeInterface)
			{
				return (int) $value->format('His')
					? $value->format('j.n.Y H:i:s')
					: $value->format('j.n.Y');
			}
			elseif (is_array($value) && isset($value['name']))
			{
				return $value['name'];
			}
			elseif (is_array($value))
			{
				return implode(', ', $value);
			}
			elseif (is_bool($value))
			{
				return $value ? 'ANO' : '';
			}

			return (string) $value;
		},
			array_values($row),
			array_keys($row)
		);
	}

	/**
	 * Vyexportuje vyfiltrovaná data
	 *
	 * @param array $ids
	 * @param string $filename
	 */
	public function actionExport(array $ids = [], $filename = 'export.csv')
	{
		/** @var Datagrid $grid */
		$grid = $this->getComponent('tblGrid');

		$query = $this->getFilteredQuery($grid->filter);
		if ($ids) {
			$query->byIDs($ids);
		}
		$data = $this->repository->fetch($query, AbstractQuery::HYDRATE_ARRAY);


		$encoder = (new CharsetConverter())
			->inputEncoding('utf-8')
			->outputEncoding('iso-8859-2');


		$csv = Writer::createFromFileObject(new \SplTempFileObject());
		$csv->addFormatter([$this, 'exportFormatter']);
		$csv->addFormatter($encoder);
		$csv->setDelimiter(';');

		$header = array_keys(current($data));
		$csv->insertOne(array_combine($header, $header));
		$csv->insertAll($data);

		$csv->output($filename);
		$this->terminate();
	}

}


