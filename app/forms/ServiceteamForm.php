<?php
/**
 * Created by PhpStorm.
 * User: Petr
 * Date: 25.08.2015
 * Time: 10:42
 */

namespace App\Forms;

use App\Forms\Controls\CroppieControl;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\ServiceteamRepository;
use App\Services\ImageService;
use Brabijan\Images\ImagePipe;
use Brabijan\Images\ImageStorage;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Http\FileUpload;
use Nette\Utils\DateTime;

/**
 * Class ServiceteamForm
 * @package App\Forms
 *
 * @method onCancel(ServiceteamForm $form)
 * @method onSave(ServiceteamForm $form)
 */
class ServiceteamForm extends Control
{
	/**
	 * @var callable[]
	 */
	public $onSave = [];

	/**
	 * @var callable[]
	 */
	public $onCancel = [];

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
	 * @var ImageService
	 */
	private $imageService;

	/**
	 * @var ImagePipe
	 */
	private $imagePipe;


	/**
	 * ServiceteamRegistrationForm constructor.
	 *
	 * @param ServiceteamRepository $groups
	 * @param ImageService          $imageService
	 * @param int                   $id
	 */
	public function __construct(ServiceteamRepository $groups, ImageService $imageService, $id)
	{
		parent::__construct();

		$this->serviceteams = $groups;
		$this->person = $this->serviceteams->find($id);
		$this->em = $this->serviceteams->getEntityManager();
		$this->imageService = $imageService;
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
	 * @return Form
	 */
	public function createComponentForm()
	{
		$frm = new Form();

		$frm->addGroup('Osobní informace');
		$frm->addText('firstName', 'Jméno')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat jméno')
			->setDisabled()
			->setDefaultValue($this->person->getFirstName());
		$frm->addText('lastName', 'Příjmení')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Přímení')
			->setDisabled()
			->setDefaultValue($this->person->getLastName());
		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->person->getNickName());

		$frm->addDatepicker('birthdate', 'Datum narození')
			->setDefaultValue($this->person->getBirthdate())
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
			->addRule(Form::RANGE, 'Podle data narození vám 7.6.2019 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('7.6.2019')->modify('-18 years')))
			->setOption('description', 'Tvoje Datum narození ve formátu dd.mm.yyyy');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->person->getAddressCity())
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
			->setOption('description', 'Město, kde aktuálně bydlíš nebo skautuješ');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('email', 'E-mail')
			->setDefaultValue($this->person->getEmail())
			->setEmptyValue('@')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
			->addRule(Form::EMAIL, 'E-mailová adresa není platná')
			->setOption('description', 'E-mail, který pravidelně vybíráš a můžem Tě na něm kontaktovat.  Budou Ti chodit informace atd..');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->person->getPhone())
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			//->addRule(Form::REGEXP,'Telefonní číslo je ve špatném formátu','/^[+0-9. ()-]*$/ui')
			->setOption('description', 'Mobilní telefon, na kterém budeš k zastižení během celé akce');

		$frm->addGroup('Doplňující údaje, aneb prozraď nám něco o sobě, ať ti můžeme najít to nejlepší zařazení ;-)');

		$frm->addSelect('arriveDate', 'Přijedu:', Serviceteam::ARRIVE_DATES)
			->setDefaultValue($this->person->getArriveDate() ? $this->person->getArriveDate()->format('Y-m-d') : null)
			->setRequired(true);
		$frm->addSelect('departureDate', 'Odjedu:', Serviceteam::DEPARTURE_DATES)
			->setDefaultValue($this->person->getDepartureDate() ? $this->person->getDepartureDate()->format('Y-m-d') : null)
			->setRequired(true);

//        $frm->addCheckbox('arrivesToBuilding', 'Přijedu na stavěcí týden od 4.6.2019')
//            ->setDefaultValue((bool) $this->person->getArrivesToBuilding());
//        $frm->addCheckbox('stayToDestroy', 'Zůstanu na bourání tábořiště v neděli')
//            ->setDefaultValue((bool) $this->person->getStayToDestroy());

		$frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obroku 2015 už před akcí')
			->setDefaultValue($this->person->getHelpPreparation());

		$frm->addCheckboxList('experience', 'Zkušenosti / Dovednosti', Serviceteam::EXPIRIENCES)
			->checkDefaultValue(false)
			->setDefaultValue($this->person->getExperience() ?: []);
		$frm->addText('experienceNote', '')
			->setAttribute('placeholder', 'Jiné')
			->setDefaultValue($this->person->getExperienceNote());

		$frm->addCheckboxList('diet', 'Strava (vegetariánská)', Serviceteam::DIET)
			->checkDefaultValue(false)
			->setDefaultValue($this->person->getDiet() ?: []);
		$frm->addText('dietNote', '')
			->setAttribute('placeholder', 'Jiné')
			->setDefaultValue($this->person->getDietNote());

		$frm->addTextArea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->person->getHealth())
			->setOption('description', 'Máš nějaké zdravotní omezení či alergii?');

		$frm->addTextArea('note', 'Poznámka')
			->setDefaultValue($this->person->getNote())
			->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu?');

		$frm->addCheckbox('wantHandbook', 'Chci dostat tištěný handbook (sešit s programem, informacemi apod.)')
			->setDefaultValue($this->person->getWantHandbook());

        $frm->addGroup('Fotografie');

		$frm->addCroppie('avatar', 'Fotka')
			->setImageUrl($this->person->getAvatar() ? $this->imageService->getImageUrl($this->person->getAvatar()) : null)
            ->setEmptyImageUrl($this->imageService->getImageUrl($this->person->isMale() ? 'avatar_boy.jpg' : 'avatar_girl.jpg'))
			->setDefaultValue($this->person->getAvatarCrop() ?: null);


		$frm->addGroup('Tričko');
		$frm->addSelect('tshirtSize', 'Velikost případného trička', Serviceteam::TSHIRT_SIZES)
			->setDefaultValue($this->person->getTshirtSize())
			->setOption('description', 'Tričko zatím bohužel úplně nemůžeme slíbit. Nicméně pravděpodobně bude :)');

		$frm->addSubmit('send', 'Uložit údaje')
			->setAttribute('class', 'btn btn-primary');

		$frm->addSubmit('cancel', 'Zrušit')
			->setAttribute('class', 'btn')
			->setValidationScope(false);

		$frm->onSuccess[] = [$this, 'processForm'];

		return $frm;
	}


	/**
	 * Zpracování formuláře
	 *
	 * @param Form $frm
	 */
	public function processForm(Form $frm)
	{
		if ($frm->getComponent('cancel')->isSubmittedBy())
		{
			$this->onCancel($this);
			return;
		}

		$values = $frm->getValues();

		// pokud jde o vytvoření nového servisáka, tak ho vytvoříme
		if (!$this->person)
		{
			$this->person = new Serviceteam();
			$this->em->persist($this->person);
		}

		/** @var \Croppie $avatar */
		$avatar = $values->avatar;
		unset($values->avatar);

		if ($avatar)
		{
			if ($image = $avatar->getFileUpload())
			{
				$filename = $this->imageService->upload($image);
				$this->person->setAvatar($filename);
			}

			$this->person->setAvatarCrop($avatar->getCrop());
		}
		else
		{
			$this->person->removeAvatar();
		}

		$values->arriveDate = $values->arriveDate ? new \DateTimeImmutable($values->arriveDate) : null;
		$values->departureDate = $values->departureDate ? new \DateTimeImmutable($values->departureDate) : null;

		// naplnime data
		foreach ($values as $key => $value)
		{
			$this->person->$key = $value;
		}

		$this->em->flush();

		$this->onSave($this, $this->person);
	}

}


/**
 * Interface IServiceteamFormFactory
 * @package App\Forms
 */
interface IServiceteamFormFactory
{
	/** @return ServiceteamForm */
	function create($id);
}
