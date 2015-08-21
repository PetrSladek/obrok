<?php

namespace  App\Module\Front\Serviceteam\Presenters;

use App\FrontBasePresenter;
use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Person;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use App\Module\Front\Presenters\GuestAuthBasePresenter;
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

class RegistrationPresenter extends GuestAuthBasePresenter
{

    /**
     * @var PersonsRepository @inject
     */
    public $persons;

    /**
     * @var SkautisHydrator
     * @inject
     */
    public $skautisHydrator;



    public function createComponentFrmRegistration() {
        
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

        $frm->addDatepicker('birthdate', 'Datum narození')
            ->setDefaultValue($this->me->birthdate)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-18 years')) )
            ->setAttribute('description','Tvoje Datum narození ve formátu dd.mm.yyyy');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->me->addressCity)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
            ->setAttribute('description','Město, kde aktuálně bydlíš nebo skautuješ');
            
            
        $frm->addGroup('Kontaktní údaje');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->me->phone)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm,'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            //->addRule(Form::REGEXP,'Telefonní číslo je ve špatném formátu','/^[+0-9. ()-]*$/ui')
            ->setAttribute('description','Mobilní telefon, na kterém budeš k zastižení během celé akce');

        $frm->addText('email', 'E-mail')
            ->setDefaultValue($this->me->email)
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

        // Zmenim na Servisaka
        $this->persons->changePersonTypeTo($this->me, Person::TYPE_SERVICETEAM);

        foreach($values as $key => $value) {
            $this->me->$key = $value;
        }

        $this->em->persist($this->me);
        $this->em->flush();

        $mail = $this->emails->create('serviceteamFirstInfo', 'První informace');
        $mail->addTo($this->me->email);
        $this->emails->send($mail);

        // Zmenila se mi role
        $this->user->login($this->me->toIdentity());

        $this->flashMessage('Byl jsi úspěšně zařazen do Servisteamu','success');
        $this->redirect('Homepage:additional');
    }
    

    
    
    
    
};
