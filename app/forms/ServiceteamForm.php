<?php
/**
 * Created by PhpStorm.
 * User: Petr
 * Date: 25.08.2015
 * Time: 10:42
 */

namespace App\Forms;


use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ServiceteamRepository;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;

class ServiceteamForm extends Control
{

    /**
     * @var callable[]
     */
    public $onSave;


    /**
     * @var EntityManager
     */
    private $em;
    /**
     * @var ServiceteamRepository
     */
    private $serviceteams;

    /**
     * @var Serviceteam
     */
    private $person;


    /**
     * ServiceteamRegistrationForm constructor.
     * @param ServiceteamRepository $serviceteams
     * @param int $id
     */
    public function __construct(ServiceteamRepository $serviceteams, $id)
    {
        parent::__construct();
        $this->serviceteams  = $serviceteams;
        $this->person   = $this->serviceteams->find($id);
        $this->em       = $this->serviceteams->getEntityManager();;
    }

    public function render() {
        $this['form']->render();
    }



    public function createComponentForm() {

        $frm = new Form();

        $frm->addGroup('Osobní informace');
        $frm->addText('firstName', 'Jméno')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat jméno')
            ->setDisabled()
            ->setDefaultValue($this->person->firstName);
        $frm->addText('lastName', 'Příjmení')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Přímení')
            ->setDisabled()
            ->setDefaultValue($this->person->lastName);
        $frm->addText('nickName', 'Přezdívka')
            ->setDefaultValue($this->person->nickName);

        $frm->addDatepicker('birthdate', 'Datum narození')
            ->setDefaultValue($this->person->birthdate)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
            ->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-18 years')) )
            ->setOption('description','Tvoje Datum narození ve formátu dd.mm.yyyy');
        $frm->addText('addressCity', 'Město')
            ->setDefaultValue($this->person->addressCity)
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
            ->setOption('description','Město, kde aktuálně bydlíš nebo skautuješ');


        $frm->addGroup('Kontaktní údaje');
        $frm->addText('email', 'E-mail')
            ->setDefaultValue($this->person->email)
            ->setEmptyValue('@')
            ->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
            ->addRule(Form::EMAIL, 'E-mailová adresa není platná')
            ->setOption('description','E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat.  Budou Ti chodit informace atd..');
        $frm->addText('phone', 'Mobilní telefon')
            ->setDefaultValue($this->person->phone)
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
            ->setDefaultValue($this->person->arrivesToBuilding ? 1 : 0);

        $frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obroku 2015 už před akcí')
            ->setDefaultValue($this->person->helpPreparation);

        $frm->addTextArea('experience', 'Zkušenosti / Dovednosti')
            ->setDefaultValue($this->person->experience);


        $frm->addTextArea('health', 'Zdravotní omezení (dieta)')
            ->setDefaultValue($this->person->health)
            ->setOption('description', 'Máš nějaké zdravotní omezení nebo dietu?');

        $frm->addTextArea('note', 'Poznámka')
            ->setDefaultValue($this->person->note)
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
            ->setDefaultValue($this->person->tshirtSize)
            ->setOption('description', 'Tričko zatím bohužel úplně nemůžeme slíbit. Nicméně pravděpodobně bude :)');

        $frm->addSubmit('send', 'Upravit svoje údaje')
            ->setAttribute('class','btn btn-primary');

        $frm->onSuccess[] = $this->processForm;

        return $frm;
    }

    public function processForm(Form $frm) {
        $values = $frm->getValues();

//        if($values->avatar && $values->avatar->hasUploadedFile())
//            $values->avatar->filename = $this->images->saveImage( $values->avatar->getUploadedFile(), 'avatars' );

        foreach($values as $key=>$value)
            $this->person->$key = $value;

        $this->em->persist( $this->person );
        $this->em->flush();


        $this->onSave($this, $this->person);
    }


}

interface IServiceteamFormFactory
{
    /** @return ServiceteamForm */
    function create($id);
}