<?php

namespace App\Module\Database\Presenters;

use App\BasePresenter;
use App\Forms\Form;
use App\Model\Entity\Serviceteam;
use App\Repositories\ServiceteamRepository;
use Kdyby\Doctrine\EntityDao;
use Kdyby\Doctrine\EntityManager;
use Nette\Bridges\ApplicationLatte\Template;
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

    /** @var EntityDao */
    public $repository;

    public $gridItemsPerPage = 100;

    /**
     * @var ArrayHash
     */
    public $acl;

    public function  startup() {
        parent::startup();

        $this->acl = new ArrayHash();
        $this->template->acl = $this->acl;

        $this->checkAllowed(array('database')); // je Servisák a má přístup k databázi?

        /** @var Serviceteam */
        $me = $this->serviceteams->find( $this->getUser()->getId() );
        $this->me = $me;
        $this->template->me = $this->me;

        if($this->isAjax()) {
            $this->redrawControl('title');
            $this->redrawControl('navbar');
            $this->redrawControl('content');
        }
    }


    /**
     * Uprava redraw control, aby se v pripade jinuho snippetu neposilal content.
     * V pripade potreby se musi invalidovat zvlast a dycky posledni
     *
     * @param null $snippet
     * @param bool $redraw
     */
    public function redrawControl($snippet = NULL, $redraw = TRUE)
    {
        if($snippet && $snippet !== 'content' && $redraw === true) // pokud je zadany jmeno nejakyho snippetu
            parent::redrawControl('content', false);

        parent::redrawControl($snippet, $redraw);
    }


    public function renderDefault() {

        /** @var Datagrid $grid */
        if($grid = $this->getComponent('tblGrid', false)) {
            /** @var Template $tpl */
            $tpl = $grid->getTemplate();

            $tpl->acl = $this->acl;
            $tpl->ageInDate = $this->ageInDate;
            $tpl->addFilter('phone', $this->phone);
        }
    }

    public function gridItemsCount($filter) {
        $count = $this->repository->countBy($filter);
        return $count;
    }
    public function gridDatasource($filter, $order, Paginator $paginator = null) { // filter pouzivam ze svyho externiho formu

        $collection = $this->repository->findBy(
            $filter,
            $order ?: ['id' => 'DESC'],
            $paginator ? $paginator->itemsPerPage : null,
            $paginator ? $paginator->offset : null
        );

        return $collection;
    }




    public function checkAllowed($role) {
        $role = is_scalar($role) ? array($role) : $role;
        $allowed = true;
        $allowed &= $this->getUser()->isLoggedIn();
        foreach($role as $r)
            $allowed &= $this->getUser()->isInRole($r);

        if(!$allowed) {
            $this->getUser()->logout();

            $this->flashMessage('Nemáte oprávnění pro vstup. Musíte se přihlásit');

            $this->redirect('Login:', array('back'=>$this->storeRequest()));
        }
    }


    public function handleAjaxEdit($id, array $data, $snippet) {

        $this->payload->status = 200;
        try {
            if(!$this->acl->edit)
                throw new \Exception('Nemáte oprávnění',300);

            $frm = $this->getComponent('frmEdit');
            if(!$frm)
                throw new \Exception("V tomto presenteru neexistuje frmEdit form");

            // prazdny pole z POSTu na null
            $data = array_map(function($item) {
                return $item === '' ? null : $item;
            }, $data);


            /** @var Form */
            $frm->setValues($data);
            $this->ajaxEdit($frm, $data, $snippet);

            $this->redrawControl($snippet);

        } catch (\Exception $e) {
            $this->payload->status = $e->getCode();
            $this->payload->message = $e->getMessage();
        }

    }
    public function ajaxEdit(Form $frm, array $data, $snippet)
    {
        // Akce
        switch($snippet) {
            default:
                foreach($data as $key=>$val) {
                    /** @var BaseControl $control */
                    $control = $frm->getComponent($key);
                    if(!$control->getRules()->validate())
                        throw new \Exception(current($control->getErrors()), 300);
                    $this->item->$key = $control->getValue();
                }
                break;
        }

        $this->em->persist($this->item);
        $this->em->flush();

    }



    public function handleRefresh($snippet = null ) {
        $this->redrawControl($snippet);
    }

    public function redrawOrRedirect() {
        if($this->isAjax()){
            $this->redrawControl();
        } else {
            $this->redirect('this');
        }
    }


    
}


