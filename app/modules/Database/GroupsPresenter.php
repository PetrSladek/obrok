<?php

namespace App\Module\Database\Presenters;

use App\Forms\Form;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Serviceteam;
use App\Query\GroupsQuery;
use App\Repositories\GroupsRepository;
use App\Repositories\ParticipantsRepository;
use App\Repositories\ServiceteamRepository;
use App\Services\ImageService;
use Kdyby\Doctrine\EntityRepository;
use Myann\CropImage;
use Nette\Application\UI\ITemplate;
use Nette\Bridges\ApplicationLatte\Template;
use Nette\Forms\Container;
use Nette\Forms\Controls\Button;
use Nette\Http\IResponse;
use Nette\Security\Passwords;
use Nette\Utils\Paginator;
use Nette\Utils\Random;
use Nextras\Datagrid\Datagrid;

class GroupsPresenter extends DatabaseBasePresenter
{

    /** @var ImageService @inject */
    public $images;


    /** @var GroupsRepository @inject */
    public $repository;

    /** @var GroupsRepository @inject */
    public $groups;


    /** @var ParticipantsRepository @inject */
    public $participants;

//    /** @var PaymentsRepository @inject */
//    public $payments;

    /** @var ServiceteamRepository @inject */
    public $serviceteams;



    /** @var array|NULL
     * @persistent
     */
    public $filter = [];


    /** @var Group */
    public $item;

    /** @var Participant */
    public $participant;


    public function startup()
    {
        parent::startup();
        $this->acl->edit = $this->user->isInRole('groups-edit');
    }




    public function actionLoginAs($id) {
        if(!$this->acl->edit)
            $this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);

        $this->item = $this->repository->getById($id);
        if(!$this->item)
            $this->error("Item not found");



        $this->participant = $this->item->getAdministrators()->fetch();

        if(!$this->participant) {
            $this->flashMessage('Tato skupina nema administratora');
            $this->redirect('default');
        }

        $hash = Random::generate(22, '0-9A-Za-z./');
        $this->participant->quickLoginHash = Passwords::hash( $hash );

        $this->em->persist($this->participant);
        $this->em->flush($this->participant);

        $this->redirect(":Participants:Login:as", $this->participant->id, $hash);
    }



    
    // DataGrid Table
    protected function createComponentTblGrid($name)
    {
        //@see http://addons.nettephp.com/cs/datagrid
        

        $grid = new Datagrid();
        $grid->setRowPrimaryKey('id');
        $grid->addCellsTemplate(__DIR__.'/templates/grid.layout.latte');
        $grid->addCellsTemplate(__DIR__.'/templates/Groups/grid.cols.latte');

        $grid->addColumn('varSymbol', 'ID / VS')->enableSort();;
        $grid->addColumn('name', 'Název')->enableSort();;
        $grid->addColumn('region', 'Kraj')->enableSort();;

        $grid->addColumn('participantsCount', 'Počet účastníků')->enableSort();;

        $grid->addColumn('confirmed', 'Přijede?')->enableSort();
        $grid->addColumn('paid', 'Zaplatil?')->enableSort();
        $grid->addColumn('arrived', 'Přijel?')->enableSort();
        $grid->addColumn('left', 'Odjel?')->enableSort();


        $grid->setFilterFormFactory(function() {

            $form = new Container();
            $form->addText('varSymbol');
            $form->addText('name');
            $form->addText('region');
//            $form->addText('participantsCount');

            $form->addSelect('confirmed', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');
            $form->addSelect('paid', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');
            $form->addSelect('arrived', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');
            $form->addSelect('left', null, array(1=>'Ano', 0=>'Ne'))->setPrompt('--');

            // set your own fileds, inputs

            // these buttons are not compulsory
            $form->addSubmit('filter', 'Vyfiltrovat')->getControlPrototype()->class = 'btn btn-primary';
            $form->addSubmit('cancel', 'Zrušit')->getControlPrototype()->class = 'btn';

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
     * @return GroupsQuery
     */
    public function getFilteredQuery($filter) {
        $query = new GroupsQuery();

        foreach($filter as $key=>$val) {
            if($key == 'varSymbol')
                $query->byVarSymbol($val);
            elseif($key == 'name')
                $query->searchName($val);
            elseif($key == 'region')
                $query->searchRegion($val);
//            elseif($key == 'confirmed')
//                $val ? $query->onlyConfirmed() : $query->onlyNotConfirmed();
//            elseif($key == 'paid')
//                $val ? $query->onlyPaid() : $query->onlyNotPaid();
//            elseif($key == 'arrived')
//                $val ? $query->onlyArrived() : $query->onlyNotArrived();
//            elseif($key == 'left')
//                $val ? $query->onlyLeft() : $query->onlyNotLeft();
        }


        return $query;
    }




    // DETAIL SKUPINY

    public function actionDetail($id) {
        $this->item = $this->repository->find($id);
        if(!$this->item)
            $this->error("Item not found");

        $this->template->item = $this->item;
    }

    public function renderDetail() {
        $this->template->activeParticipants = $this->item->getConfirmedParticipants();
        $this->template->canceledParticipants = $this->item->getUnconfirmedParticipants();
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


        $frm->addSelect('boss','Vedoucí skupiny (18+)', $this->item->getPossibleBosses($this->ageInDate))
            ->setDefaultValue($this->item && $this->item->getBoss() ? $this->item->getBoss()->id : null)
            ->setPrompt('- Vyberte vedoucího sk. -');
        
//        $frm->addText('paidFor','Zaplaceno za')
//            ->setDefaultValue($this->item ? $this->item->paidFor : null)
//            ->addRule(Form::INTEGER, 'Musí být celé číslo')
//            ->setDefaultValue(0);

//        $frm->addCropImage('avatar', 'Obrázek skupiny')
//            ->setAspectRatio( 1 )
//            ->setUploadScript($this->link('Image:upload'))
//            ->setCallbackImage(function(CropImage $cropImage) {
//                return $this->images->getImage($cropImage->getFilename());
//            })
//            ->setCallbackSrc(function(CropImage $cropImage, $width, $height) {
//                return $this->images->getImageUrl($cropImage->getFilename(), $width, $height);
//            })
//            ->setDefaultValue( new CropImage(Group::$defaultAvatar) );

        $frm->addSubmit('send', 'Uložit')->setAttribute('class', 'btn btn-success btn-lg btn-block');
        $frm->onSuccess[] = [$this, 'frmEditSuccess'];

        return $frm;
    }


    public function frmEditSuccess($frm)
    {
        $values = $frm->getValues();

        if(!$this->item) {
            $this->item = new Group();
            $this->em->persist($this->item);
        }



        foreach($values as $key => $value) {
            if($key == 'boss')
                $value = $this->participants->find($value);

            $this->item->$key = $value;
        }


        $this->em->flush();

        $this->flashMessage('Údaje úspěšně uloženy', 'success');
        $this->redirect('detail', $this->item->id);
    }



    public function createComponentTblPayments()
    {
        $grid = new Datagrid();
        $grid->setRowPrimaryKey('id');
        $grid->addCellsTemplate(__DIR__.'/../templates/grid.layout.latte');
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

        $grid->setFilterFormFactory(function() {
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
        $grid->setDatasourceCallback(function($filter, $order, Paginator $paginator = null) { // filter pouzivam ze svyho externiho formu

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

        if($snippet == 'boss')
//        {
            $this->redrawControl('phone');
//        } elseif($snippet == 'paidFor') {
//            $this->redrawControl('flags');
//            $this->redrawControl('participants');
//        }

    }






    public function handleStatus($status = 'confirmed', $value = true) {
        if(!$this->acl->edit)
            $this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);

        try {
            if(!in_array($status,['confirmed','paid','arrived','left']))
                throw new \InvalidArgumentException("Wrong status name");

            // nastavi vsem aktivnim ucastnikum stav
            foreach($this->item->getConfirmedParticipants() as $participant) {
                // zavola metody setConfirmed, setPaid,...
                $method = "set" . ucfirst($status);
                $participant->$method($value);
            }

            $this->em->flush();
        } catch(\InvalidArgumentException $e) {
            $this->flashMessage( $e->getMessage(), 'danger' );
        }

        $this->redrawControl('flags');
        $this->redrawControl('participants');
        if(!$this->isAjax())
            $this->redirect('this');
    }

    public function handleParticipantStatus($participantId, $status = 'confirmed', $value = true) {
        if(!$this->acl->edit)
            $this->error('Nemate opravneni', IResponse::S403_FORBIDDEN);

        $this->participant = $this->participants->find($participantId);
        if(!$this->participant)
            $this->error('Účastník neexistuje');


        try {
            if(!in_array($status,['confirmed','paid','arrived','left']))
                throw new \InvalidArgumentException("Wrong status name");

            // zavola metody setConfirmed, setPaid,...
            $method = "set".ucfirst($status);
            $this->participant->$method($value);

//            $this->em->persist($this->participant);
            $this->em->flush();
        } catch(\InvalidArgumentException $e) {
            $this->flashMessage( $e->getMessage(), 'danger' );
        }

        $this->redrawControl('flags');
        $this->redrawControl('participants');
        if(!$this->isAjax())
            $this->redirect('this');
    }






    public function renderConfirmations($id = null) {

        ini_set('max_execution_time', 0);
		ini_set('memory_limit','1024M');

        $conditions = ['confirmed' => 1];
        if($id)
            $conditions['id'] = $id;

        /** @var ICollection $collection */
        $collection = $this->repository->findBy($conditions);

        $template =  $this->createTemplate();
        $template->setFile( $this->context->expand("%appDir%/ModuleDatabase/templates/Groups/confirmations.latte"));

        $list = $collection->fetchAll();
        $template->list = $list;

//        $this->template->list = $list;
        $this->sendResponse( new PdfResponse( $template ) );
    }



    public function renderPayment($id = null) {

        ini_set('max_execution_time', 0);
		ini_set('memory_limit','1024M');

        $conditions = ['confirmed' => 1];
        if($id)
            $conditions['id'] = $id;

        /** @var ICollection $collection */
        $collection = $this->repository->findBy($conditions);

        $template =  $this->createTemplate();
        $template->setFile( $this->context->expand("%appDir%/ModuleDatabase/templates/Groups/payment.latte"));

        $list = $collection->fetchAll();
		$template->list = $list;
		//$this->template->list = $list;

		$pdf = new PdfResponse( $template );
		$pdf->pageFormat = 'A5';
//		$pdf->pageOrientaion = PdfResponse::ORIENTATION_LANDSCAPE;
		$this->sendResponse($pdf);
	}




    public function actionSetRegionToGroups() {
        echo '<pre>';

        /** @var Group[] $groups */
        $groups = $this->groups->findBy(['region='=>null]);
        foreach($groups as $group) {

            $data = file_get_contents("http://maps.googleapis.com/maps/api/geocode/json?address=".urlencode($group->city.' ,Česká republika')."&sensor=false&language=cs");
            $data = json_decode($data);

            if(empty($data->results[0]->address_components))
                continue;
            $region = null;
            foreach($data->results[0]->address_components as $row) {
                if($row->types[0] == 'administrative_area_level_1' && $row->types[1] == 'political') {
                    $region = $row->long_name;
                    break;
                }
            }
            $group->region = $region;
            $this->groups->persistAndFlush($group);
            echo "{$group->city} : {$group->region}\n";

        }
        echo "Hotovo\n";
        $this->terminate();
    }

    public function actionTranslateRegions() {
        //echo '<pre>';
        $conn = Dibi::getConnection();

        $conn->query("UPDATE groups SET region = 'Plzeňský kraj' WHERE region = 'Plzeň Region'");
        $conn->query("UPDATE groups SET region = 'Jihomoravsý kraj' WHERE region = 'South Moravian Region'");
        $conn->query("UPDATE groups SET region = 'Králové Hradecký kraj' WHERE region = 'Hradec Králové Region'");
        $conn->query("UPDATE groups SET region = 'Jihočeský kraj' WHERE region = 'South Bohemian Region'");
        $conn->query("UPDATE groups SET region = 'Středočeský kraj' WHERE region = 'Central Bohemian Region'");
        $conn->query("UPDATE groups SET region = 'Moravsko-slezký kraj' WHERE region = 'Moravian-Silesian Region'");
        $conn->query("UPDATE groups SET region = 'Liberecký kraj' WHERE region = 'Liberec Region'");
        $conn->query("UPDATE groups SET region = 'Pardubický kraj' WHERE region = 'Pardubice Region'");
        $conn->query("UPDATE groups SET region = 'Zlínský kraj' WHERE region = 'Zlin Region'");
        $conn->query("UPDATE groups SET region = 'Hlavní město Praha' WHERE region = 'Hlavní město Praha'");
        $conn->query("UPDATE groups SET region = 'Kraj Ústí nad Labem' WHERE region = 'Ústí nad Labem Region'");
        $conn->query("UPDATE groups SET region = 'Kraj Vysočina' WHERE region = 'Vysočina Region'");
        $conn->query("UPDATE groups SET region = 'Olomoucký kraj' WHERE region = 'Olomouc Region'");
        $conn->query("UPDATE groups SET region = 'Kraj Karlovy Vary' WHERE region = 'Karlovy Vary Region'");

        echo "Hotovo\n";
        $this->terminate();
    }



}



