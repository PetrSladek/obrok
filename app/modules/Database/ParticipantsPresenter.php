<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Query\ParticipantsQuery;
use App\Repositories\GroupsRepository;
use App\Repositories\ParticipantsRepository;
use Nette\Forms\Container;
use Nette\Forms\Controls\BaseControl;
use Nette\Forms\IControl;
use Nette\Http\IResponse;
use Nette\Security\Passwords;
use Nette\Utils\DateTime;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;


class ParticipantsPresenter extends DatabaseBasePresenter
{

    /** @var ParticipantsRepository @inject */
    public $repository;
    /** @var GroupsRepository @inject */
    public $groups;


//    /** @var array|NULL
//     * @persistent
//     */
//    public $filter = [];

    /** @var Participant */
    public $item;



    public function startup()
    {
        parent::startup();
        $this->acl->edit = $this->user->isInRole('groups-edit');
    }



    
    // DataGrid Table
    protected function createComponentTblGrid($name)
    {
        $grid = new Datagrid();

        $grid->setRowPrimaryKey('id');
        $grid->addCellsTemplate(__DIR__.'/../templates/grid.layout.latte');
        $grid->addCellsTemplate(__DIR__.'/../templates/Participants/grid.cols.latte');

        $grid->addColumn('id', 'Id')->enableSort();
        $grid->addColumn('fullname', 'Jméno')->enableSort();
        $grid->addColumn('group', 'Skupina')->enableSort();

        $grid->addColumn('age', 'Věk')->enableSort();
        $grid->addColumn('contact', 'Kontakt')->enableSort();
        $grid->addColumn('address', 'Adresa')->enableSort();

        $grid->addColumn('confirmed', 'Přijede?')->enableSort();
        $grid->addColumn('paid', 'Zaplatil?')->enableSort();
        $grid->addColumn('arrived', 'Přijel?')->enableSort();
        $grid->addColumn('left', 'Odjel?')->enableSort();


        $grid->setFilterFormFactory(function() {

            $form = new Container();
            $form->addText('id');
            $form->addText('fullname');
            $form->addText('group');
            $form->addText('age');
            $form->addText('contact');
            $form->addText('address');

            $form->addSelect('confirmed', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--')->setDefaultValue(true);
            $form->addSelect('paid', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');
            $form->addSelect('arrived', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');
            $form->addSelect('left', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');

            // these buttons are not compulsory
            $form->addSubmit('filter', 'Vyfiltrovat');
            $form->addSubmit('cancel', 'Zrušit');

            return $form;
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
     * @return ParticipantsQuery
     */
    public function getFilteredQuery($filter) {
        $query = new ParticipantsQuery();

        foreach($filter as $key=>$val) {
            if($key == 'id')
                $query->byId($val);
            elseif($key == 'fullname')
                $query->searchFullname($val);
            elseif($key == 'group')
                $query->searchGroup($val);
            elseif($key == 'age')
                $query->byAge($val, $this->ageInDate);
            elseif($key == 'contact')
                $query->searchContact($val);
            elseif($key == 'address')
                $query->searchAddress($val);

            elseif($key == 'confirmed')
                $val ? $query->onlyConfirmed() : $query->onlyNotConfirmed();
//            elseif($key == 'paid')
//                $val ? $query->onlyPaid() : $query->onlyNotPaid();
            elseif($key == 'arrived')
                $val ? $query->onlyArrived() : $query->onlyNotArrived();
            elseif($key == 'left')
                $val ? $query->onlyLeft() : $query->onlyNotLeft();
        }

        // Pida do selectu zavislosti aby se pak nemuseli tahat solo
        $query->withGroup();

        return $query;
    }

    
    // DETAIL ÚĆASTNÍKA

    public function actionDetail($id) {
        $this->item = $this->repository->find($id);
        if(!$this->item)
            $this->error("Item not found");
    }



    public function createComponentFrmAddProgram() {

//        $programs = $this->database->query("
//          SELECT
//            p.id as program_id,
//            b.id as block_id,
//            p.start, DATE_ADD(p.start, INTERVAL b.duration*15 MINUTE) as end,
//            b.duration, b.name, b.tools, b.location, b.perex, b.lectorExt as lector,
//            b.capacity,
//            (SELECT COUNT(*) FROM program_user WHERE program_id = p.id) as occupied
//          FROM program p
//          JOIN block b ON b.id = p.block_id
//          ORDER BY start ASC, name ASC
//        ");
//
//
        $select = [];
//
//        foreach($programs->fetchAll() as $program) {
//            $day = $this->day($program->start);
//            $startAt = $program->start->format('H:i');
//            $endAt = $program->end->format('H:i');
//            $lector = $program->lector && $program->lector != '-' ? "/{$program->lector}/" : null;
//            $capacity = "({$program->occupied} / {$program->capacity})";
//            $select[$program->program_id] = "[$day $startAt-$endAt] {$program->name} {$lector} {$capacity}";
//        }

        $frm = new Form();
        $frm->getElementPrototype()->class('ajax form-horizontal');
        $frm->addSelect('program', null, $select)
            ->setPrompt('- vyberte program -');
        $frm->addSubmit('send', 'Zaregistrovat program');


        $frm->onSuccess[] = callback($this, 'frmAddProgramSuccess');


        return $frm;
    }

    public function handleDeleteProgram($idProgram) {
        $this->database->query("DELETE FROM program_user WHERE participant_id = ? AND program_id = ?", $this->item->id, $idProgram);

        $this->flashMessage( 'Program úspěšně odhlášen.', 'success' );
        if($this->isAjax()) {
            $this->redrawControl('detail', false);
            $this->redrawControl('programs');
            $this->redrawControl('flashes');
        } else $this->redirect('this');

    }

    public function frmAddProgramSuccess(Form $frm) {
        $values = $frm->getValues();
        $program_id = $values->program;

        $program =  $this->database->query("
          SELECT
            p.id as program_id,
            b.id as block_id,
            p.start, DATE_ADD(p.start, INTERVAL b.duration*15 MINUTE) as end,
            b.duration, b.name, b.tools, b.location, b.perex, b.lectorExt as lector,
            b.capacity,
            (SELECT COUNT(*) FROM program_user WHERE program_id = p.id) as occupied
          FROM program p
          LEFT JOIN block b ON b.id = p.block_id
          WHERE p.id = ?
        ", $program_id)->fetch();

        try {
            if ($program == null)
                throw new \Exception('Program s tímto id neexistuje');

            if ($program->block_id == null)
                throw new \Exception('Na blok, který nemá přiřazen žádný program se nelze přihlásit.');

            if ($program->occupied >= $program->capacity)
                throw new \Exception('Kapacita programu je již plná.');

            $exist = $this->database->query("SELECT * FROM program_user WHERE participant_id = ? AND program_id = ?", $this->item->id, $program_id)->fetch();
            if ($exist)
                throw new \Exception('Uživatel je již přihlášen na tento program');

            $otherProgram = $this->database->query("
              SELECT
                p.start,
                DATE_ADD(p.start, INTERVAL b.duration*15 MINUTE) as end,
                b.name
              FROM program_user pu
              JOIN program p ON (p.id = pu.program_id AND pu.participant_id = ?)
              JOIN block b ON b.id = p.block_id
              HAVING
                start = ?
                OR (start > ? AND start < ?)
                OR (end > ? AND end < ?)
                OR (start < ? AND end > ?)
            ", $this->item->id,
                $program->start,
                $program->start, $program->end,
                $program->start, $program->end,
                $program->start, $program->end)->fetch();
            $hasOtherProgram = $otherProgram ? true : false;

            if ($hasOtherProgram)
                throw new \Exception("V tuto dobu máte přihlášený již jiný program ({$otherProgram->name}).");

//            if ($this->item->hasOtherProgramInSection($program, $this->basicBlockDuration))
//                throw new \Exception("V sekci {$program->programSection} máte přihlášený již jiný program");

            $this->database->query("INSERT INTO program_user (participant_id, program_id) VALUES (?, ?)", $this->item->id, $program_id);
            $this->flashMessage( 'Program úspěšně přihlášen.', 'success' );

        } catch (\Exception $e) {
            $this->flashMessage( $e->getMessage(), 'danger' );
        }

        if($this->isAjax()) {
            $this->redrawControl('detail', false);
            $this->redrawControl('programs');
            $this->redrawControl('flashes');

        } else $this->redirect('this');
    }

//    protected function hasUserOtherProgram($program) {
//
//        {
//            foreach ($this->programs as $otherProgram) {
//                if ($otherProgram->id == $program->id) continue;
//                if ($otherProgram->start == $program->start) return true; // zacina stejne
//                if ($otherProgram->start > $program->start && $otherProgram->start < $program->end) return true; // nebo zacina poydej ale driv nez tenhle skonci
//                if ($otherProgram->end > $program->start && $otherProgram->end < $program->end return true; // konci po mmem porgtramu ale driv
//                if ($otherProgram->start < $program->start && $otherProgram->countEnd($basicBlockDuration) > $program->countEnd($basicBlockDuration)) return true;
//            }
//            return false;
//
//    }



    public function renderDetail($id) {

        $this->template->programs = []; //$this->getParticipantProgram($id);
        $this->template->item = $this->item;
    }






    public function actionEdit($id = null) {

        if(!$this->acl->edit)
            $this->error('Nemáte oprávnění', IResponse::S401_UNAUTHORIZED);

        if($id) {
            $this->item = $this->repository->find($id);
            if(!$this->item)
                $this->error("Item not found");
        }
        $this->template->item = $this->item;
    }

    public function createComponentFrmEdit() {

        $frm = new Form();

        $frm->addGroup('Skupina');

        $frm->addSelect('group', 'Skupina do které uživatel patří', $this->groups->findPairs("name"))
            ->setDefaultValue($this->item ? $this->item->group->id : $this->getParameter('toGroup'))
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
            ->setDefaultValue($this->item ? $this->item->lastName : null);


        $frm->addDatepicker('birthdate', 'Datum narození:')
            ->setDefaultValue($this->item ? $this->item->birthdate->format('j.n.Y') : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-15 years')) )
            ->addRule(Form::RANGE, 'Podle data narození vám 10.6.2015 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('10.6.2015')->modify('-25 years'), null) );

//            ->addRule(callback('Participant','validateAge'), 'Věk účastníka Obroku 2015 musí být od 15 do 24 let');

        $frm->addRadioList('gender', 'Pohlaví',array(Person::GENDER_MALE=>'muž',Person::GENDER_FEMALE=>'žena'))
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
            ->setAttribute('title','E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat. Budou Ti chodit informace atd..')
            ->setAttribute('data-placement','right');
        $frm->addText('phone', 'Mobilní telefon',null,13)
            ->setDefaultValue($this->item ? $this->item->phone : null)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            ->setAttribute('title','Mobilní telefon, na kterém budeš k zastižení během celé akce')
            ->setAttribute('data-placement','right');

        $frm->addGroup('Zdravotní omezení');
        $frm->addTextarea('health', 'Zdravotní omezení a alergie')
            ->setDefaultValue($this->item ? $this->item->health : null);

        $frm->addCheckbox('admin','Administrátor skupiny')
            ->setDefaultValue($this->item ? $this->item->isAdmin() : null);

        $frm->addTextarea('noteInternal', 'Interní poznámka')
            ->setDefaultValue($this->item ? $this->item->noteInternal : null);

        $frm->addSubmit('send', 'Uložit údaje účastníka')
            ->setAttribute('class','btn btn-primary');

        $frm->onSuccess[] = callback($this, 'frmEditSubmitted');


        return $frm;
    }

    public function frmEditSubmitted(Form $frm) {
        $values = $frm->getValues();

        if(!$this->item)
            $this->item = new Participant();

        foreach($values as $key => $value) {
            if($key == 'group')
                $value = $this->groups->find($value);

            $this->item->$key = $value;
        }

        $this->em->persist($this->item->group);

        $this->em->persist($this->item);
        $this->em->flush();

        $this->flashMessage('Údaje úspěšně uloženy', 'success');
        $this->redirect('detail', $this->item->id);

    }




    public function actionLoginAs($id) {
        if(!$this->acl->edit)
            $this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);

        $this->item = $this->repository->find($id);
        if(!$this->item)
            $this->error("Item not found");

        $hash = Random::generate(22, '0-9A-Za-z./');
        $this->item->quickLoginHash = Passwords::hash( $hash );

        $this->em->persist($this->item);
        $this->em->flush();

        $this->redirect(":Participants:Login:as", $id, $hash);
    }



}



