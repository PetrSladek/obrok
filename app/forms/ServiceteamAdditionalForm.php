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

class ServiceteamAdditionalForm extends Control
{

    /**
     * @var array of function($this, $person)
     */
    public $onAdditionalSave;


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
     * @param ServiceteamRepository $groups
     * @param int $id
     */
    public function __construct(ServiceteamRepository $groups, $id)
    {
        parent::__construct();
        $this->serviceteams  = $groups;
        $this->person   = $this->serviceteams->find($id);
        $this->em       = $this->serviceteams->getEntityManager();;
    }

    public function render() {
        $this['form']->render();
    }



    public function createComponentForm() {

        $frm = new Form();

        $frm->addGroup('Doplňující údaje, aneb prozraď nám něco o sobě, ať ti můžeme najít to nejlepší zařazení ;-)');

        $frm->addSelect('arrivesToBuilding', 'Kdy přijedu?', array(
            1=>'Přijedu i na stavěcí týden od 7.6.2015',
            0=>'Můžu jen na Obrok',
        ))
            ->setDefaultValue($this->person->arrivesToBuilding ? 1 : 0);

        $frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obrok 2015 už před akcí');

//        $frm->addCheckbox('helpSzt', 'Mám zdravotnické vzdělání a chci pomoct SZT (Skautský záchranný tým)');
//        $frm->addCheckbox('helpSos', 'Mám zájem pomáhat SOSce (Skautská ochraná služba)');

        $frm->addTextArea('experience', 'Zkušenosti / Dovednosti')
            ->setDefaultValue($this->person->experience)
            ->setAttribute('class','input-xxlarge');

        $frm->addTextArea('health', 'Zdravotní omezení (dieta)')
            ->setDefaultValue($this->person->health)
            ->setAttribute('class','input-xxlarge')
            ->setOption('description', 'Máš nějaké zdravotní omezení nebo dietu?')
            ->setAttribute('data-placement','right');

        $frm->addTextArea('note', 'Poznámka')
            ->setDefaultValue($this->person->note)
            ->setAttribute('class','input-xxlarge')
            ->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu?')
            ->setAttribute('data-placement','right');

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
//            ->setDefaultValue( new CropImage(Serviceteam::$defaultAvatar) )
//            ->addRule(Form::FILLED, 'Musíš nahrát fotku');

        $frm->addGroup('Tričko');
        $frm->addSelect('tshirtSize','Velikost případného trička', Serviceteam::$tShirtSizes)
            ->setDefaultValue($this->person->tshirtSize)
            ->setOption('description', 'Tričko zatím bohužel uplně nemůžeme slíbit. Nicméně pravděpodobně bude :)')
            ->setDefaultValue("man-L"); // L

        $frm->addSubmit('send', 'Dokončit registraci')
            ->setAttribute('class','btn btn-primary');

//        $defaults = $this->person->toArray();
//        $defaults['avatar'] = $this->person->getAvatar();
//        $frm->setDefaults($defaults);

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


        $this->onAdditionalSave($this, $this->person);
    }


}

interface IServiceteamAdditionalFormFactory
{
    /** @return ServiceteamAdditionalForm */
    function create($id);
}