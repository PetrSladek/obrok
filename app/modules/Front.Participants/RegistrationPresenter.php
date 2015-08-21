<?php

namespace App\Module\Front\Participants\Presenters;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;

use App\Forms\Form;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Module\Front\Presenters\GuestAuthBasePresenter;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Html;


class RegistrationPresenter extends GuestAuthBasePresenter
{


    /**
     * @var ParticipantsRepository @inject
     */
    public $participants;

    /**
     * @var GroupsRepository @inject
     */
    public $groups;


    /** @var Group */
    public $group;

    /** @var Participant */
    public $participant;


    /** @var SkautisHydrator @inject */
    public $skautisHydrator;



    /** @var ArrayHash */
    public $defaults;




    // REGISTRACE NOVE SKUPINY

    public function createComponentFrmRegistration() {

        $frm = new Form();

        $frm->addGroup('Informace o skupině');
        $frm->addText('name', 'Název skupiny')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setOption('description', 'Název skupiny, pod kterým budete vystupovat (např. RK Másla)');
        $frm->addText('city', 'Město')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setOption('description', 'Město, ke kterému se skupina "hlásí", ve kterém funguje');
        $frm->addTextarea('note', 'O skupině')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
            ->setOption('description', 'Napište nám krátkou charakteristku vaší skupiny. Jste fungující kmen nebo skupina "jednotlivců"? Čím se zabýváte? Napiště něco, co by ostatní mohlo zajímat!');


        $frm->addGpsPicker('location', 'Mapa roverských kmenů:', [
            'zoom' => 11,
            'size' => [
                'x' => '100%',
                'y' => '400',
            ]])
            ->setOption('description', 'Píchnete špendlík vašho kmene do mapy, a pomozte tím vytvoření Mapy českého roveringu');

        $frm->addSubmit('send', 'Zaregistrovat Skupinu na Obrok 2015')
            ->setAttribute('class','btn btn-primary btn-block');

        $frm->onSuccess[] = callback($this, 'frmRegistrationSubmitted');


        if($this->defaults)
            $frm->setDefaults($this->defaults);

        return $frm;
    }

    public function frmRegistrationSubmitted(Form $frm) {

        $values = $frm->getValues();


        $group = new Group();
        $group->name = $values->name;
        $group->city = $values->city;
        $group->note = $values->note;

        $group->locationLat = $values->location->lat;
        $group->locationLng = $values->location->lng;

        $this->em->persist($group);
        $this->em->flush();

        $this->flashMessage('Skupina byla vytvořena! Ted se ještě musíš zaregistrovat ty :)','success');
        $this->redirect('toGroup', $group->id, $group->getInvitationHash( $this->config->hashKey ));
    }



    // REGISTRACE DO EXISTUJICI SKUPINY

    public function actionToGroup($id, $hash) {
        $this->group = $this->groups->find($id);
        if(!$this->group)
            $this->error("Skupina #$id neexistuje");

        if($this->group->getInvitationHash(  $this->config->hashKey ) !== $hash)
            $this->error("Pokus o napadeni");


//        if(!$this->openRegistrationParticipants/* && !$this->group->getFreePlaces()*/) {
//            $this->flashMessage('Nelze registrovat nové učastníky. Kapacita je již zaplněná', 'warning');
//            $this->redirect('Homepage:');
//        }

        $this->template->group = $this->group;
  }


    public function createComponentFrmNewParticipant() {
        $frm = new Form();

        $frm->addGroup('Osobní informace');

        $frm->addText('firstName', 'Jméno')
            ->setDisabled()
            ->setDefaultValue($this->me->firstName);
        $frm->addText('lastName', 'Příjmení')
            ->setDisabled()
            ->setDefaultValue($this->me->lastName);

        $frm->addText('nickName', 'Přezdívka')
            ->setDefaultValue($this->me->nickName);

        $frm->addDatepicker('birthdate', 'Datum narození:')
            ->setDefaultValue($this->me->birthdate)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-15 years')) )
            ->addRule(Form::RANGE, 'Podle data narození vám 10.6.2015 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('10.6.2015')->modify('-25 years'), null) );

        $frm->addRadioList('gender', 'Pohlaví', [Person::GENDER_MALE=>'muž',Person::GENDER_FEMALE=>'žena'])
            ->setDefaultValue($this->me->gender)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

        $frm->addGroup('Trvalé bydliště');
        $frm->addText('addressStreet', 'Ulice a čp.')
            ->setDefaultValue($this->me->addressStreet)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->me->addressCity)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
        $frm->addText('addressPostcode', 'PSČ')
            ->setDefaultValue($this->me->addressPostcode)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

        $frm->addGroup('Kontaktní údaje');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->me->phone)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm,'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            ->setAttribute('description','Mobilní telefon, na kterém budeš k zastižení během celé akce');
//        $frm->addCheckbox('phoneIsSts', 'je v STS?')
//            ->setAttribute('description','Je toto telefoní číslo v Skautské telefoní síti?');
        $frm->addText('email', 'E-mail:')
            ->setDefaultValue($this->me->email)
            ->addRule(Form::FILLED, 'Zadejte E-mail')
            ->setOption('description','Tvůj email na který ti budou chodit informace');

        $frm->addGroup('Zdravotní omezení');
        $frm->addTextarea('health', 'Zdravotní omezení a alergie')
            ->setDefaultValue($this->me->health);


        $frm->addGroup(null);
        $frm->addCheckbox('conditions', Html::el()->setHtml('Souhlasím s <a target="_blank" href="http://www.obrok15.cz/registrace/">podmínkami účasti na akci</a> a s <a target="_blank" href="http://www.obrok15.cz/obecna-ustanoveni-storno-podminky/">obecnými ustanoveními</a>'))
            ->addRule($frm::FILLED, 'Musíte souhlasit s podmínkami účasti')
            ->setOmitted();

        $frm->addSubmit('send', 'Zaregistrovat se')
            ->setAttribute('class','btn btn-primary btn-block');


        $frm->onSuccess[] = [$this, 'frmFrmNewParticipantSubmitted'];

        return $frm;
    }

    public function frmFrmNewParticipantSubmitted(Form $frm) {

        $values = $frm->getValues();

        $this->persons->changePersonTypeTo($this->me, Person::TYPE_PARTICIPANT);

        // Prednactenej ze SkautISu;
        foreach($values as $key => $value)
            $this->me->$key = $value;

        if(!$this->group->hasAdmin()) {
            $this->me->setAdmin();
        }

        $this->me->setGroup( $this->group );


        // Pokud skupina nema sefa a tomuhle ucastnikovi je nad 18, tak z nej udaleme sefa
        if(!$this->group->hasBoss() && $this->me->getAge( $this->ageInDate ) >= 18) {
            $this->group->setBoss( $this->me );
        }

//        $this->em->persist($this->me);
//        $this->em->persist($this->group);
        $this->em->flush();


        $mail = $this->emails->create('participantFirstInfo', 'První informace');
        $mail->addTo($this->me->email);
        $this->emails->send($mail);

        // Zmenila se mi role
        $this->user->login($this->me->toIdentity());

        $this->redirect('Homepage:');
    }



    
    
    
    
};
