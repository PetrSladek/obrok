<?php

namespace App\Module\Front\Participants\Presenters;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Forms\Form;
use App\Repositories\GroupsRepository;
use App\Services\ImageService;
use Nette\Utils\AssertionException;
use Nette\Utils\DateTime;

/**
 * FrontEnd ServiceteamPresenter
 *
 * @author     Petr /Peggy/ Sládek
 * @package    Obrok15
 */

class HomepagePresenter extends ParticipantAuthBasePresenter
{

    /** @var ImageService @inject */
    public $images;

    /** @var GroupsRepository @inject */
    public $groups;

    /** @var Participant */
    public $participant;




    public function renderDefault() {

        $this->template->programs = $this->getParticipantProgram( $this->me->id );
    }


    // EDIT GROUP

    public function actionEditGroup() {
        if(!$this->me->isAdmin()) {
            $this->flashMessage('Musíte být administrátorem skupiny, abyste mohl měnit její údaje!');
            $this->redirect('Homepage:');
        }


        $this->template->data = $this->me->group;
    }

    public function createComponentFrmEditGroup() {

        $frm = new Form();

        $frm->addGroup('Základní informace');
        $frm->addText('name', 'Název skupiny')
            ->setDefaultValue($this->me->group->name)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setAttribute('title','Název skupiny, pod kterým budete vystupovat (např. RK Másla)')
            ->setAttribute('data-placement','right');
        $frm->addText('city', 'Město')
            ->setDefaultValue($this->me->group->city)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setAttribute('title','Město, ke kterému se skupina "hlásí", ve kterém funguje')
            ->setAttribute('data-placement','right');
        $frm->addTextarea('note', 'O skupině')
            ->setDefaultValue($this->me->group->note)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setAttribute('class','input-xxlarge')
            ->setAttribute('title','Napišt nám krátkou charakteristku vaší skupiny. Jste fungující kmen nebo skupina "jednotlivců"? Čím se zabýváte? Napiště něco, co by ostatní mohlo zajímat!')
            ->setAttribute('data-placement','right');

        $frm->addSelect('boss','Vedoucí skupiny (18+)', $this->me->group->getPossibleBosses( $this->ageInDate ))
            ->setDefaultValue($this->me->group->boss ? $this->me->group->boss->id : null)
            ->setPrompt('- Vyberte vedoucího skupiny -');
//            ->addCondition(Form::FILLED)
//                ->addRule(callback('Participant','validateBossAge'), 'Věk vedoucího skupiny Obroku 2015 musí být 18 let nebo více');

        $frm->addGroup('Doplňující údaje');
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
        $frm->addGpsPicker('location', 'Mapa roverských kmenů:', [
            'zoom' => 11,
            'size' => [
                'x' => '100%',
                'y' => '400',
            ]])
            ->setDefaultValue($this->me->group->locationLng !== null && $this->me->group->locationLng !== null ? [$this->me->group->locationLat, $this->me->group->locationLng] : null)
            ->setOption('description', 'Píchnete špendlík vašho kmene do mapy, a pomozte tím vytvoření Mapy českého roveringu');

        $frm->addSubmit('send', 'Uložit údaje skupiny')
            ->setAttribute('class','btn btn-primary');

        $frm->onSuccess[] = callback($this, 'frmEditGroupSubmitted');

//        $defaults = $this->me->group->toArray(IEntity::TO_ARRAY_RELATIONSHIP_AS_ID);
//        $defaults['avatar'] = $this->me->group->getAvatar();
//        if($this->me->group->locationLat && $this->me->group->locationLng)
//            $defaults['location'] = ['lat'=>$this->me->group->locationLat, 'lng'=>$this->me->group->locationLng ];
//        $frm->setDefaults($defaults);

        return $frm;
    }

    public function frmEditGroupSubmitted(Form $frm) {
        $values = $frm->getValues();

        $values->locationLat = $values->location->lat;
        $values->locationLng = $values->location->lng;
        unset($values->location);

//        if($values->avatar && $values->avatar->hasUploadedFile())
//            $values->avatar->filename = $this->images->saveImage( $values->avatar->getUploadedFile(), 'groups' );

        foreach($values as $key=>$value) {
            if($key == 'boss')
                $value = $this->participants->find($value); // najdu entitu bosse
            $this->me->group->$key = $value;
        }

        $this->em->persist($this->me->group);
        $this->em->flush();

        $this->flashMessage('Údaje úspěšně upraveny','success');
        $this->redirect('default');
    }



    public function handleGoBack($id) {

        $this->participant = $this->participants->find($id);
        if(!$this->participant)
            $this->error("Item not found");
        if($this->participant->group !== $this->me->group)
            $this->error("Access denied");

        $this->participant->setConfirmed(true);
        $this->em->flush();

        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }

    public function handleGoOut($id) {

        $this->participant = $this->participants->find($id);
        if(!$this->participant)
            $this->error("Item not found");
        if($this->participant->group !== $this->me->group)
            $this->error("Access denied");

        $this->participant->setConfirmed(false);
        $this->em->flush();

        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }




    // ADD-EDIT PARTICIPANT
    public function actionParticipant($id = null) {
        if($id) {
            $this->participant = $this->participants->find($id);
            if(!$this->participant)
                $this->error('Účastník neexistuje');
            if($this->participant->group->id != $this->me->group->id)
                $this->error('Účastník není z vaší skupiny');
            if( !($this->participant->id == $id || $this->me->isAdmin()) )
                $this->error('Nejste administrator skupiny, muzete editovat jen sebe.');
        } elseif( true  ) {
            $this->error('Nelze přidávat členy jinak, než zasláním pozvánky');
        } elseif( !$this->me->isAdmin()  ) {
            $this->error('Členy může přidávat jen administrátor skupiny');
        }


        $this->template->item = $this->participant;
    }

    public function createComponentFrmParticipant() {

        $frm = new Form();

        $frm->addGroup('Osobní informace');

        $frm->addText('firstName', 'Jméno')
            ->setDefaultValue($this->participant ? $this->participant->firstName : null)
            ->setRequired();
        $frm->addText('lastName', 'Příjmení')
            ->setDefaultValue($this->participant ? $this->participant->lastName : null)
            ->setRequired();
        $frm->addText('nickName', 'Přezdívka')
            ->setDefaultValue($this->participant ? $this->participant->nickName : null);

        $frm->addDatepicker('birthdate', 'Datum narození:')
            ->setDefaultValue($this->participant ? $this->participant->birthdate : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-15 years')) )
            ->addRule(Form::RANGE, 'Podle data narození vám 10.6.2015 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('10.6.2015')->modify('-25 years'), null) );

//            ->addRule(callback('Participant','validateAge'), 'Věk účastníka Obroku 2015 musí být od 15 do 24 let');

        $frm->addRadioList('gender', 'Pohlaví',array('male'=>'muž','female'=>'žena'))
            ->setDefaultValue($this->participant ? $this->participant->gender : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

        $frm->addGroup('Trvalé bydliště');
        $frm->addText('addressStreet', 'Ulice a čp.')
            ->setDefaultValue($this->participant ? $this->participant->addressStreet : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->participant ? $this->participant->addressCity : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
        $frm->addText('addressPostcode', 'PSČ')
            ->setDefaultValue($this->participant ? $this->participant->addressPostcode : null)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

        $frm->addGroup('Kontaktní údaje');
        $frm->addText('email', 'E-mail')
            ->setDefaultValue($this->participant ? $this->participant->email : null)
            ->setEmptyValue('@')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
            ->addRule(Form::EMAIL, 'E-mailová adresa není platná')
            ->setAttribute('title','E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat. Budou Ti chodit informace atd..')
            ->setAttribute('data-placement','right');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->participant ? $this->participant->phone : null)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            ->setAttribute('title','Mobilní telefon, na kterém budeš k zastižení během celé akce')
            ->setAttribute('data-placement','right');


        $frm->addGroup('Zdravotní omezení');
        $frm->addTextarea('health', 'Zdravotní omezení a alergie')
            ->setDefaultValue($this->participant ? $this->participant->health : null);

        // admin
        if($this->me->isAdmin()) {
            $frm->addCheckbox('admin','Administrátor skupiny')
                ->setDefaultValue($this->participant ? (bool) $this->participant->admin : null);
        }

        $frm->addSubmit('send', 'Uložit údaje účastníka')
            ->setAttribute('class','btn btn-primary');

        $frm->onSuccess[] = callback($this, 'frmParticipantSubmitted');


        if($this->participant) {

            if($this->participant->id != $this->me->id) {
                $frm['email']->setAttribute('title','E-mail, který pravidelně vybírá. Budou tam chodit informace atd..');
                $frm['phone']->setAttribute('title','Mobilní telefon, na kterém bude k zastižení během celé akce');
            }
            $frm['firstName']->setDisabled()->setRequired(false)->setDefaultValue( $this->participant->firstName );
            $frm['lastName']->setDisabled()->setRequired(false)->setDefaultValue( $this->participant->lastName );

//            $defaults = $this->participant->toArray(IEntity::TO_ARRAY_RELATIONSHIP_AS_ID);
//            $defaults['admin'] = ($this->participant->role == 'admin');
//            $frm->setDefaults($defaults);
        }


        return $frm;
    }

    public function frmParticipantSubmitted(Form $frm) {
        $values = $frm->getValues();

        if(!$this->participant) {
            $this->participant = new Participant();
            $this->participant->setGroup($this->me->group);

            $this->em->persist( $this->participant );
        }

        foreach($values as $key=>$value)
            $this->participant->$key = $value;

        $this->em->flush();

        // Pokud sem to já aktualizuju objekt me
        if($this->me->id == $this->participant->id)
            $this->me = $this->participant;

        $this->flashMessage('Údaje úspěšně upraveny','success');
        if($this->isAjax()) {
            $this->redrawControl();
        } else {
            $this->redirect('default');
        }

    }


    
    
    public function createComponentFrmCancel() {
        
        $frm = new Form();
        
        $frm->addGroup(null);
        $frm->addTextarea('reason', 'Důvod zrušní účasti')
            ->addRule(Form::FILLED, 'Prosím zadej důvod proč rušíš svou účast.');

        $frm->addSubmit('send', 'Ano opravdu na Obrok nepřijedu')
            ->setAttribute('class','btn btn-primary');
        
        $frm->onSuccess[] = callback($this, 'frmCancelSubmitted');
        
        return $frm;
    }
    
    public function frmCancelSubmitted(Form $frm) {

        // vsechny ucastniky oznacim jako ze neprijedou
        foreach($this->me->group->getConfirmedParticipants() as $participant)
            $participant->setConfirmed(false);

        $this->me->group->noteInternal .= "\nDůvod zrušení učasti: ".$frm->values->reason;

        $this->em->flush();
        
        $this->flashMessage('Účast Vaší skupiny na Obroku byla zrušena. Účty zůstanou přístupné, ale už se s Vámi nepočítá!');
        $this->redirect('Homepage:');
    }


    
    public function createComponentFrmSendInvitationLink() {
        $frm = new Form();
        $frm->addTextArea('emails', 'E-mailové adresy')
            ->setOption('description', 'Na každý řádek jednu e-mailovou adresu');
        
        $frm->addSubmit('send', 'Pozvat účastníky');
    
        $frm->onSuccess[] = $this->frmSendInvitationLinkSuccess;
    
        return $frm;
    }
    
    public function frmSendInvitationLinkSuccess(Form $frm){
        $values = $frm->getValues();
        $emails = array_map('trim', explode("\n", trim($values->emails)));

        $link = $this->link('//Registration:toGroup', $this->me->group->id, $this->me->group->getInvitationHash($this->config->hashKey));

        try {
            $mail = $this->emails->create('participantInvitationLink', "Pozvánka do skupiny", array('group'=>$this->me->group, 'link'=>$link), $this);
            foreach($emails as $to)
                $mail->addTo($to);
            $this->emails->send($mail);

            $this->flashMessage("E-maily s pozvánkou do skupiny jsou uspěšně rozeslány", 'success');
        } catch(AssertionException $e) {
            $frm['emails']->addError("Některý z emailů není ve správném tvaru", 'danger');
            return;
        }

    
        $this->isAjax() ? $this->redrawControl() : $this->redirect('this');
    }


    
};