<?php

namespace App\Module\Front\Serviceteam\Presenters;

use App\Forms\Form;
use App\Model\Entity\Serviceteam;
use App\Services\ImageService;
use Kdyby\Doctrine\EntityRepository;
use Nette\Utils\DateTime;

/**
 * FrontEnd ServiceteamPresenter
 *
 * @author     Petr /Peggy/ Sládek
 * @package    Obrok15
 */

class HomepagePresenter extends ServiceteamAuthBasePresenter
{

    /** @var ImageService @inject */
    public $images;

    /** @var EntityRepository */
    public $serviceteam;
    
    public function createComponentFrmEdit() {
        $frm = new Form();
        
        $frm->addGroup('Osobní informace');
        $frm->addText('firstName', 'Jméno')
             ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat jméno')
             ->setDisabled()
             ->setDefaultValue($this->me->firstName);
        $frm->addText('lastName', 'Příjmení')
             ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Přímení')
             ->setDisabled()
             ->setDefaultValue($this->me->lastName);
        $frm->addText('nickName', 'Přezdívka')
            ->setDefaultValue($this->me->nickName);

        $frm->addDatepicker('birthdate', 'Datum narození')
            ->setDefaultValue($this->me->birthdate)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-18 years')) )
            ->setOption('description','Tvoje Datum narození ve formátu dd.mm.yyyy');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->me->addressCity)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
            ->setOption('description','Město, kde aktuálně bydlíš nebo skautuješ');
            
            
        $frm->addGroup('Kontaktní údaje');
        $frm->addText('email', 'E-mail')
            ->setDefaultValue($this->me->email)
            ->setEmptyValue('@')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
            ->addRule(Form::EMAIL, 'E-mailová adresa není platná')
            ->setOption('description','E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat.  Budou Ti chodit informace atd..');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->me->phone)
            ->setEmptyValue('+420')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
            ->addRule([$frm,'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
            //->addRule(Form::REGEXP,'Telefonní číslo je ve špatném formátu','/^[+0-9. ()-]*$/ui')
            ->setOption('description','Mobilní telefon, na kterém budeš k zastižení během celé akce');
        
            
        $frm->addGroup('Doplňující údaje, aneb prozraď nám něco o sobě, ať ti můžeme najít to nejlepší zařazení ;-)');

        $frm->addSelect('arrivesToBuilding', 'Kdy přijedu?', array(
            1=>'Přijedu i na stavěcí týden od 7.6.2015',
            0=>'Můžu jen na Obrok',
        ))
            ->setDefaultValue($this->me->arrivesToBuilding ? 1 : 0);

        $frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obroku 2015 už před akcí')
            ->setDefaultValue($this->me->helpPreparation);
        
        $frm->addTextArea('experience', 'Zkušenosti / Dovednosti')
            ->setDefaultValue($this->me->experience);


        $frm->addTextArea('health', 'Zdravotní omezení (dieta)')
            ->setDefaultValue($this->me->health)
            ->setOption('description', 'Máš nějaké zdravotní omezení nebo dietu?');

        $frm->addTextArea('note', 'Poznámka')
            ->setDefaultValue($this->me->note)
            ->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu?');

//        $frm->addGroup('Fotografie');
//        $frm->addCropImage('avatar', 'Fotka')
//            ->setAspectRatio( 1 )
//            ->setUploadScript($this->link('Image:upload'))
//            ->setCallbackImage(function(CropImage $cropImage) {
//                return $this->images->getImage($cropImage->getFilename());
//            })
//            ->setCallbackSrc(function(CropImage $cropImage, $width, $height) {
//                return $this->images->getImageUrl($cropImage->getFilename(), $width, $height);
//            })
//            ->setDefaultValue( new CropImage(Serviceteam::$defaultAvatar) );
////            ->addRule(Form::FILLED, 'Musíš nahrát fotku');

        $frm->addGroup('Tričko');
        $frm->addSelect('tshirtSize','Velikost případného trička', Serviceteam::$tShirtSizes)
            ->setDefaultValue($this->me->tshirtSize)
            ->setOption('description', 'Tričko zatím bohužel úplně nemůžeme slíbit. Nicméně pravděpodobně bude :)');

        $frm->addSubmit('send', 'Upravit svoje údaje')
            ->setAttribute('class','btn btn-primary');
        
        $frm->onSuccess[] = callback($this, 'frmEditSubmitted');


        return $frm;
    }
    
    public function frmEditSubmitted(Form $frm) {
        $values = $frm->getValues();


        // Nastavim data
        foreach($values as $key=>$value)
            $this->me->$key = $value;

        // persistuju do DB
        $this->em->persist( $this->me );
        $this->em->flush( $this->me );
        
        $this->flashMessage('Údaje úspěšně upraveny','success');
        $this->redirect('default');
    }
    
    
    public function actionEdit() {

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


//        $this->me->setStatus( Serviceteam::STATUS_CANCELED );

        $this->me->confirmed = false; // neprijede

        $this->me->noteInternal .= "\nDůvod zrušení učasti: ".$frm->getValues()->reason;

        $this->em->persist( $this->me );
        $this->em->flush();
        
        $this->flashMessage('Tvoje účast na obroku byla úspěšně zrušena. Tento účet zůstane aktivní, ale už se s tebou na Obroku nepočítá.');
        $this->redirect('Homepage:');
    }




    // DOKONCENI REGISTRACE
    public function actionAdditional() {

    }
    public function createComponentFrmAdditional() {

        $frm = new Form();

        $frm->addGroup('Doplňující údaje, aneb prozraď nám něco o sobě, ať ti můžeme najít to nejlepší zařazení ;-)');

        $frm->addSelect('arrivesToBuilding', 'Kdy přijedu?', array(
            1=>'Přijedu i na stavěcí týden od 7.6.2015',
            0=>'Můžu jen na Obrok',
        ))
            ->setDefaultValue($this->me->arrivesToBuilding ? 1 : 0);

        $frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obrok 2015 už před akcí');

//        $frm->addCheckbox('helpSzt', 'Mám zdravotnické vzdělání a chci pomoct SZT (Skautský záchranný tým)');
//        $frm->addCheckbox('helpSos', 'Mám zájem pomáhat SOSce (Skautská ochraná služba)');

        $frm->addTextArea('experience', 'Zkušenosti / Dovednosti')
            ->setDefaultValue($this->me->experience)
            ->setAttribute('class','input-xxlarge');

        $frm->addTextArea('health', 'Zdravotní omezení (dieta)')
            ->setDefaultValue($this->me->health)
            ->setAttribute('class','input-xxlarge')
            ->setOption('description', 'Máš nějaké zdravotní omezení nebo dietu?')
            ->setAttribute('data-placement','right');

        $frm->addTextArea('note', 'Poznámka')
            ->setDefaultValue($this->me->note)
            ->setAttribute('class','input-xxlarge')
            ->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu?')
            ->setAttribute('data-placement','right');

        $frm->addGroup('Fotografie');
//        $frm->addCropImage('avatar', 'Fotka')
//            ->setAspectRatio( 1 )
//            ->setUploadScript($this->link('Image:upload'))
//            ->setCallbackImage(function(CropImage $cropImage) {
//                return $this->images->getImage($cropImage->getFilename());
//            })
//            ->setCallbackSrc(function(CropImage $cropImage, $width, $height) {
//                return $this->images->getImageUrl($cropImage->getFilename(), $width, $height);
//            })
//            ->setDefaultValue( new CropImage(Serviceteam::$defaultAvatar) )
//            ->addRule(Form::FILLED, 'Musíš nahrát fotku');

        $frm->addGroup('Tričko');
        $frm->addSelect('tshirtSize','Velikost případného trička', Serviceteam::$tShirtSizes)
            ->setDefaultValue($this->me->tshirtSize)
            ->setOption('description', 'Tričko zatím bohužel uplně nemůžeme slíbit. Nicméně pravděpodobně bude :)')
            ->setDefaultValue("man-L"); // L

        $frm->addSubmit('send', 'Dokončit registraci')
            ->setAttribute('class','btn btn-primary');

        $frm->onSuccess[] = callback($this, 'frmAdditionalSubmitted');

//        $defaults = $this->me->toArray();
//        $defaults['avatar'] = $this->me->getAvatar();
//        $frm->setDefaults($defaults);

        return $frm;
    }
    public function frmAdditionalSkip() {
        $this->flashMessage('Prosíme nezapomeň doplnit doplňující údaje','info');
        $this->redirect('Homepage:');
    }

    public function frmAdditionalSubmitted(Form $frm) {
        $values = $frm->getValues();

//        if($values->avatar && $values->avatar->hasUploadedFile())
//            $values->avatar->filename = $this->images->saveImage( $values->avatar->getUploadedFile(), 'avatars' );

        foreach($values as $key=>$value)
            $this->me->$key = $value;

        $this->em->persist( $this->me );
        $this->em->flush();

        $this->flashMessage('Doplňující údaje úspěšně přidány','success');
        $this->redirect('Homepage:');
    }
    
};