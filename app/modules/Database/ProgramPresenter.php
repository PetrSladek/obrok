<?php

namespace App\Module\Database\Presenters;

use App\Group;
use App\GroupsRepository;
use App\Participant;
use App\ParticipantsRepository;
use App\PaymentsRepository;
use App\Serviceteam;
use App\ServiceteamRepository;
use Myann\CropImage;
use MyAnn\Form;
use Myann\ImageService;
use Nette\Forms\Container;
use Nette\Forms\Controls\Button;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;
use Nextras\Orm\Collection\ICollection;
use Nextras\Orm\Entity\IEntity;
use PdfResponse\PdfResponse;
use Tracy\Debugger;

class ProgramPresenter extends DatabaseBasePresenter
{


    /** @var array|NULL
     * @persistent
     */
    public $filter = [];

    public function renderDefault() {

    }

    public function beforeRender()
    {
        parent::beforeRender();
        $this['tblGrid']->template->registerHelper('day' , callback($this, 'day'));
    }


    public function createComponentTblGrid()
    {
        $grid = new Datagrid();

        $grid->setRowPrimaryKey('id');
        $grid->addCellsTemplate(__DIR__.'/templates/grid.layout.latte');
        $grid->addCellsTemplate(__DIR__.'/templates/Program/grid.cols.latte');

        $grid->addColumn('id', 'ID')->enableSort();
        $grid->addColumn('section', 'Sekce')->enableSort();

        $grid->addColumn('time', 'Čas')->enableSort();

        $grid->addColumn('name', 'Název')->enableSort();
        $grid->addColumn('capacity', 'Kapacita')->enableSort();


        $grid->setFilterFormFactory(function() {
            $frm = new Container();
            $frm->addText('id')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER);

//            $frm->addMultiSelect('section', null, array(
//                'cesta' => 'Cesta',
//                'sluzba' => 'Služba',
//                'zivly' => 'Živly',
//                'vapro1' => 'Vapro 1.blok',
//                'vapro2' => 'Vapro 2.blok',
//                'vaproM' => 'Vapro přesobědový meziblok',
//                'vapro3' => 'Vapro 3.blok',
//                'vapro4' => 'Vapro 4.blok',
//            ));

            $frm->addText('name')
                ->addCondition(Form::FILLED)
                ->addRule(Form::INTEGER);


            // these buttons are not compulsory
            $frm->addSubmit('filter', 'Vyfiltrovat')->getControlPrototype()->class = 'btn btn-primary';
            $frm->addSubmit('cancel', 'Zrušit')->getControlPrototype()->class = 'btn';

//            $form->setDefaults($this->filter);

            return $frm;
        });


        $grid->setDatasourceCallback(function($filter, $order, Paginator $paginator = null) { // filter pouzivam ze svyho externiho formu

            $where = [];
            $where[] = '1=1';
            foreach($filter as $k => $v) {
                if($k == 'id')
                    $where[] = "p.id = '$v'"; // TODO SQL INJECTION
                elseif($k == 'name')
                    $where[] = "(b.name LIKE '%$v%' OR b.lectorExt LIKE '%$v%')"; // TODO SQL INJECTION

            }

            $programs = $this->database->query("
              SELECT
                p.id as id,
                p.start, DATE_ADD(p.start, INTERVAL b.duration*15 MINUTE) as end,
                b.duration, b.name, b.tools, b.location, b.perex, b.lectorExt as lector,
                b.capacity,
                (SELECT COUNT(*) FROM program_user WHERE program_id = p.id) as occupied
              FROM program p
              JOIN block b ON b.id = p.block_id
              WHERE ".implode(" AND ", $where)."
              ORDER BY start ASC, name ASC
            ")->fetchAll();

            foreach($programs as &$program) {
                if($program->start->format('Y-m-d H:i') == "2015-06-09 17:00") {
                    $program->programSection = 'Cesta';
                    $program->programSubSection = null;
                } else if($program->start == DateTime::from("2015-06-11 8:00")) {
                    $program->programSection = 'Služba';
                    $program->programSubSection = null;
                } else if($program->start >= DateTime::from("2015-06-11 13:45") && $program->start <= DateTime::from("2015-06-11 18:30")) {
                    $program->programSection = 'Živly';
                    $program->programSubSection = null;
                } else if($program->start->format('Y-m-d H:i') == "2015-06-13 09:30") {
                    $program->programSection = 'Vapro';
                    $program->programSubSection = '1. blok';
                } else if($program->start->format('Y-m-d H:i') == "2015-06-13 11:30") {
                    $program->programSection = 'Vapro';
                    $program->programSubSection = '2. blok';
                } else if($program->start->format('Y-m-d H:i') == "2015-06-13 13:15") {
                    $program->programSection = 'Vapro';
                    $program->programSubSection = 'Obědový meziblok';
                } else if($program->start->format('Y-m-d H:i') == "2015-06-13 15:00") {
                    $program->programSection = 'Vapro';
                    $program->programSubSection = '3. blok';
                } else if($program->start->format('Y-m-d H:i') == "2015-06-13 17:00") {
                    $program->programSection = 'Vapro';
                    $program->programSubSection = '4. blok';
                }
            }

            return $programs;
        });

        return $grid;
    }





}



