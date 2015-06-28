<?php

namespace  App\Module\Front\Serviceteam\Presenters;

use App\FrontBasePresenter;
use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Person;
use App\Repositories\ServiceteamRepository;
use App\ServiceteamBasePresenter;
use App\Model\Entity\Serviceteam;
use App\Forms\Form;
use Nette\Utils\DateTime;
use Nette\Utils\Html;


/**
 * FrontEnd ServiceteamPresenter
 *
 * @author     Petr /Peggy/ Sládek
 * @package    Obrok15
 */

class RegistrationPresenter extends \App\Module\Front\Presenters\FrontBasePresenter
{

    /**
     * @var ServiceteamRepository @inject
     */
    public $serviceteams;


    /**
     * @var Serviceteam
     */
    public $serviceteam;

    /**
     * @var SkautisHydrator
     * @inject
     */
    public $skautisHydrator;

    public function startup()
    {
        parent::startup();

        if(!$this->user->isLoggedIn()) {
            $this['skautisLogin']->open(); // otevre prihlasovaci formular skautisu a pak presmeruje zpet sem
        }
        if($this->user->isInRole(Person::ROLE_PARTICIPANT)) {
            $this->flashMessage('Už si zaregistrovaný jako účastník. Nemůžeš se registrovat znovu!', 'warning');
            $this->redirect(':Front:Participants:Homepage:');
        }
        elseif($this->user->isInRole(Person::ROLE_SERVICETEAM)) {
            $this->flashMessage('Už si zaregistrovaný jako Servisák. Nemůžeš se registrovat znovu!', 'warning');
            $this->redirect('Homepage:');
        }

    }


    public function actionDefault() {
        $this->serviceteam = new Serviceteam();
        $this->skautisHydrator->hydrate($this->serviceteam, $this->skautis->getPersonId());
    }

    public function createComponentFrmRegistration() {
        
        $frm = new Form();
        
        $frm->addGroup('Osobní informace');

        $frm->addText('firstName', 'Jméno')
            ->setDisabled()
            ->setDefaultValue($this->serviceteam->firstName);
        $frm->addText('lastName', 'Příjmení')
            ->setDisabled()
            ->setDefaultValue($this->serviceteam->lastName);
        $frm->addText('nickName', 'Přezdívka')
            ->setDefaultValue($this->serviceteam->nickName);

        $frm->addDatepicker('birthdate', 'Datum narození')
            ->setDefaultValue($this->serviceteam->birthdate)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-18 years')) )
            ->setAttribute('description','Tvoje Datum narození ve formátu dd.mm.yyyy');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->serviceteam->addressCity)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
            ->setAttribute('description','Město, kde aktuálně bydlíš nebo skautuješ');
            
            
        $frm->addGroup('Kontaktní údaje');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->serviceteam->phone)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm,'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            //->addRule(Form::REGEXP,'Telefonní číslo je ve špatném formátu','/^[+0-9. ()-]*$/ui')
            ->setAttribute('description','Mobilní telefon, na kterém budeš k zastižení během celé akce');

        $frm->addText('email', 'E-mail')
            ->setDefaultValue($this->serviceteam->email)
            ->setEmptyValue('@')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
            ->addRule(Form::EMAIL, 'E-mailová adresa není platná')
            ->setAttribute('description','Kontaktní e-mail na který vám budou chodit informace');


        $frm->addCheckbox('conditions', Html::el()->setHtml('Souhlasím s <a target="_blank" href="http://www.obrok15.cz/registrace/">podmínkami účasti na akci</a> a s <a target="_blank" href="http://www.obrok15.cz/obecna-ustanoveni-storno-podminky/">obecnými ustanoveními</a>'))
            ->addRule($frm::FILLED, 'Musíte souhlasit s podmínkami účasti')
            ->setOmitted();

        //$frm->addGroup(null);   
        $frm->addSubmit('send', 'Registrovat se do servis týmu')
            ->setAttribute('class','btn btn-primary btn-block');

        $frm->onSuccess[] = callback($this, 'frmRegistrationSubmitted');

        
        return $frm;
    }
    
    public function frmRegistrationSubmitted(Form $frm) {
        $values = $frm->getValues();


        foreach($values as $key => $value) {
            $this->serviceteam->$key = $value;
        }

        $this->em->persist($this->serviceteam);
        $this->em->flush();

        $mail = $this->emails->create('serviceteamFirstInfo', 'První informace');
        $mail->addTo($this->serviceteam->email);
        $this->emails->send($mail);
        
        $this->user->login($this->serviceteam->toIdentity());
        
        $this->flashMessage('Byl jsi úspěšně zařazen do Servisteamu','success');
        $this->redirect('Homepage:additional');
    }
    

    
    
    
    
};
