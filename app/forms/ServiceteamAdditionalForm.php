<?php

namespace App\Forms;

use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ServiceteamRepository;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\Html;

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
	 *
	 * @param ServiceteamRepository $groups
	 * @param int                   $id
	 */
	public function __construct(ServiceteamRepository $groups, $id)
	{
		parent::__construct();
		$this->serviceteams = $groups;
		$this->person = $this->serviceteams->find($id);
		$this->em = $this->serviceteams->getEntityManager();;
	}


	/**
	 * Vykreslí komponentu s formulářem
	 */
	public function render()
	{
		$this['form']->render();
	}


	/**
	 * Vytvoří formulář
	 *
	 * @return Form
	 */
	public function createComponentForm()
	{

		$frm = new Form();

		$frm->addGroup('Příjezd a odjezd');

		$frm->addSelect('arriveDate', 'Přijedu:', Serviceteam::ARRIVE_DATES)
            ->checkDefaultValue(false)
			->setDefaultValue($this->person->getArriveDate() ? $this->person->getArriveDate()->format('Y-m-d') : null)
            ->setOption('description', Html::el('')
                ->addHtml('<strong>Pro nadšence a stavěče budou na pondělní a úterní večer nachytané posezení s dobrým jídlem</strong>')
            )
			->setRequired(true);

		$frm->addSelect('departureDate', 'Odjedu:', Serviceteam::DEPARTURE_DATES)
            ->checkDefaultValue(false)
            ->setDisabled(true)
			->setDefaultValue('2019-05-26')
            ->setOption('description', Html::el('')
                ->addHtml('<span>S odjezdem počtej v neděli odpoledne, budeme bourat celé tábořiště. Jako odměnu máme nachystaný skvělý nedělní oběd s poděkováním!</span>')
            )
			->setRequired(true);


//		$frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obrok 2019 už před akcí');
//      $frm->addCheckbox('helpSzt', 'Mám zdravotnické vzdělání a chci pomoct SZT (Skautský záchranný tým)');
//      $frm->addCheckbox('helpSos', 'Mám zájem pomáhat SOSce (Skautská ochraná služba)');

        $frm->addGroup('Činnosti');

		$frm->addCheckboxList('experience', 'Zajímá mě:', Serviceteam::EXPIRIENCES)
			->checkDefaultValue(false)
			->setDefaultValue($this->person->getExperience() ?: []);
		$frm->addText('experienceNote', 'Jiné')
			->setDefaultValue($this->person->getExperienceNote())
			->setAttribute('class', 'input-xxlarge');
        $frm->addCheckbox('speakEnglish', "Domluvím se anglicky")
            ->setDefaultValue($this->person->isSpeakEnglish());

        $frm->addGroup('Zájmy a záliby');
        $frm->addTextArea('hobbies', 'Umíš něco, co by chtěl umět každý (žonglovat, pískat na prsty, triky s kartami, skákat šipku,..)? Nebo něco, co těmoc baví a co si sám troufáš ostatní učit (nějaký sport, divadlo, hudba, příroda či cokoli dalšího)? Pokud máš nějaký instruktorský kurz (horolezectví, plavčík, vodní turistika,..), prosím, i tohle nám napiš, abychom mohli udělat program, který bude bavit i tebe!! Každá tvá superschopnost nás zajímá!!')
            ->setDefaultValue($this->person->getHobbies())
            ->setAttribute('class', 'input-xxlarge')
            ->setHtmlAttribute('rows', 10);

        $frm->addGroup('Strava');

		$frm->addSelect('diet', 'Strava', Serviceteam::DIET)
			->checkDefaultValue(false)
			->setDefaultValue($this->person->getDiet() ?: null);

        $frm->addCheckboxList('dietSpecification', '', Serviceteam::DIET_SPECIFICATION)
            ->checkDefaultValue(false)
            ->setDefaultValue($this->person->getDietSpecification() ?: []);

		$frm->addText('dietNote', '')
			->setAttribute('placeholder', 'Jiné omezení')
			->setDefaultValue($this->person->getDietNote());


        $frm->addGroup('Ostatní');

        $frm->addSelect('wantHandbook', 'Handbook',  [
                0 => 'Stačí mi elektronický, šetřím naše lesy',
                1 => 'Potřebuji i papírovou verzi'
            ])
            ->checkDefaultValue(false)
            ->setDefaultValue(0);


        $frm->addTextArea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->person->getHealth())
			->setAttribute('class', 'input-xxlarge')
			->setOption('description', Html::el('')->setHtml('Pokud máte nějaký handicap a potřebujete poradit, může se kdykoliv ozvat zde: Ladislava Blažková <a href="mailto:ladkablazkova@gmail.com">ladkablazkova@gmail.com</a> | +420 728 120 498'))
			->setAttribute('data-placement', 'right');

        $frm->addSelect('tshirtSize', 'Velikost případného trička', Serviceteam::TSHIRT_SIZES)
            ->setDefaultValue($this->person->getTshirtSize())
            ->setOption('description', 'Tričko zatím bohužel uplně nemůžeme slíbit. Nicméně pravděpodobně bude :)')
            ->setDefaultValue("man-L"); // L

		$frm->addTextArea('note', 'Poznámka')
			->setDefaultValue($this->person->getNote())
			->setAttribute('class', 'input-xxlarge')
			->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu nebo podobně?')
			->setAttribute('data-placement', 'right');

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



		$frm->addSubmit('send', 'Dokončit registraci')
			->setAttribute('class', 'btn btn-primary');

//        $defaults = $this->person->toArray();
//        $defaults['avatar'] = $this->person->getAvatar();
//        $frm->setDefaults($defaults);

		$frm->onSuccess[] = [$this, 'processForm'];

		return $frm;
	}


	/**
	 * Zpracování formuláře
	 * @param Form $frm
	 */
	public function processForm(Form $frm)
	{
		$values = $frm->getValues();

//        if($values->avatar && $values->avatar->hasUploadedFile())
//            $values->avatar->filename = $this->images->saveImage( $values->avatar->getUploadedFile(), 'avatars' );

		$values->arriveDate = $values->arriveDate ? new \DateTimeImmutable($values->arriveDate) : null;
//		$values->departureDate = $values->departureDate ? new \DateTimeImmutable($values->departureDate) : null;

		foreach ($values as $key => $value)
		{
			$this->person->$key = $value;
		}

		$this->em->persist($this->person);
		$this->em->flush();

		$this->onAdditionalSave($this, $this->person);
	}

}


interface IServiceteamAdditionalFormFactory
{
	/** @return ServiceteamAdditionalForm */
	function create($id);
}