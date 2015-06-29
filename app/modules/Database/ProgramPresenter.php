<?php

namespace App\Module\Database\Presenters;


use App\Forms\Form;
use App\Model\Entity\Program;
use App\Query\ProgramsQuery;
use App\Repositories\ProgramsRepository;
use App\Repositories\ProgramsSectionsRepository;
use Nette\Forms\Container;
use Nette\Http\IResponse;
use Nette\Utils\DateTime;
use Nette\Utils\Paginator;
use Nextras\Datagrid\Datagrid;

class ProgramPresenter extends DatabaseBasePresenter
{

    /** @var ProgramsRepository @inject */
    public $repository;

    /**
     * @var ProgramsSectionsRepository @inject
     */
    public $sections;


    /** @var Program */
    public $item;

    public function startup()
    {
        parent::startup();
        $this->acl->edit = $this->user->isInRole('database'); // todo program-edit
    }


    public function createComponentTblGrid()
    {
        $grid = new Datagrid();

        $grid->setRowPrimaryKey('id');
        $grid->addCellsTemplate(__DIR__.'/templates/grid.layout.latte');
        $grid->addCellsTemplate(__DIR__.'/templates/Program/grid.cols.latte');

        $grid->addColumn('id', 'ID')->enableSort();
        $grid->addColumn('section', 'Sekce')->enableSort();

        $grid->addColumn('time', 'Den a čas')->enableSort();

        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('capacity', 'Obsazeno');//->enableSort();


        $grid->setFilterFormFactory(function() {
            $frm = new Container();
            $frm->addText('id')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER);

            $sections = [];
            foreach($this->sections->findAll() as $section)
                $sections[$section->id] = $section->title . ($section->subTitle ? " - {$section->subTitle}" : null);
            $frm->addMultiSelect('section', null, $sections);

            $frm->addText('name')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER);

            $frm->addSelect('capacity', null, array(1=>'Plné', 0=>'Volné'))->setPrompt('--');


            // these buttons are not compulsory
            $frm->addSubmit('filter', 'Vyfiltrovat');
            $frm->addSubmit('cancel', 'Zrušit');

//            $form->setDefaults($this->filter);

            return $frm;
        });


        $grid->setPagination($this->gridItemsPerPage, function($filter) {
            $query = $this->getFilteredQuery($filter);
            return $query->count($this->repository);
        });
        $grid->setDatasourceCallback(function($filter, $sorting, Paginator $paginator = null) {

            $query = $this->getFilteredQuery($filter);

            $result = $this->repository->fetch($query);

            if($paginator)
                $result->applyPaging($paginator->getOffset(), $paginator->getLength());
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
     * @return ProgramsQuery
     */
    public function getFilteredQuery($filter) {
        $query = new ProgramsQuery();

        foreach($filter as $key=>$val) {
            if($key == 'id')
                $query->byId($val);
            elseif($key == 'section')
                $query->inSections($val);
            elseif($key == 'name')
                $query->searchName($val);
            elseif($key == 'capacity')
                $val ? $query->onlyFull() : $query->onlyNotFull();
        }

        return $query;
    }



    // DETAIL PROGRAMU

    public function actionDetail($id) {
        $this->item = $this->repository->find($id);
        if(!$this->item)
            $this->error("Item not found");

        $this->template->item = $this->item;
    }



    public function actionEdit($id = null) {
        if(!$this->acl->edit)
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);

        if($id) {
            $this->item = $this->repository->find($id);
            if (!$this->item)
                $this->error("Item not found");
        }
        $this->template->item = $this->item;
    }


    public function createComponentFrmEdit() {
        $frm = new Form();
        $frm->setAjax();

        $sections = [];
        foreach($this->sections->findAll() as $section)
            $sections[$section->id] = $section->title . ($section->subTitle ? " - {$section->subTitle}" : null);

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

        $frm->addDateTimePicker('start', 'Začátek programu:') //DatePicker
            ->setDefaultValue($this->item ? $this->item->start : null)
            ->addRule(Form::FILLED);
        $frm->addDateTimePicker('end', 'Konec programu:') //DatePicker
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


    public function frmEditSuccess(Form $frm)
    {
        $values = $frm->getValues();

        if(!$this->item) {
            $this->item = new Program();
            $this->em->persist($this->item);
        }

        foreach($values as $key => $value) {
            if($key == 'section')
                $value = $value ? $this->sections->find($value) : null;

            $this->item->$key = $value;
        }

        $this->em->flush();

        $this->flashMessage('Údaje úspěšně uloženy', 'success');
        $this->redirect('detail', $this->item->id);
    }




}



