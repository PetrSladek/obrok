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
use Nette\Utils\Html;

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
            ->addRule(Form::RANGE, 'Podle data narození vám 22.5.2019 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('22.5.2019')->modify('-18 years')))
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
                ->addHtml('<span>S odjezdem počítej v neděli odpoledne, budeme bourat celé tábořiště. Jako odměnu máme nachystaný skvělý nedělní oběd s poděkováním!</span>')
            )
            ->setRequired(true);

//		$frm->addCheckbox('helpPreparation', 'Mám zájem a možnost pomoct s přípravami Obroku 2019 už před akcí')
//			->setDefaultValue($this->person->getHelpPreparation());
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
        $frm->addTextArea('hobbies', 'Umíš něco, co by chtěl umět každý (žonglovat, pískat na prsty, triky s kartami, skákat šipku,..)? Nebo něco, co tě moc baví a co si sám troufáš ostatní učit (nějaký sport, divadlo, hudba, příroda či cokoli dalšího)? Pokud máš nějaký instruktorský kurz (horolezectví, plavčík, vodní turistika,..), prosím, i tohle nám napiš, abychom mohli udělat program, který bude bavit i tebe!! Každá tvá superschopnost nás zajímá!!')
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
            ->setDefaultValue($this->person ? $this->person->getWantHandbook() : 0);

		$frm->addTextArea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->person->getHealth())
            ->setOption('description', Html::el('')->setHtml('Pokud máte nějaký handicap a potřebujete více informací, může se kdykoliv ozvat zde: Ladislava Blažková <a href="mailto:ladkablazkova@gmail.com">ladkablazkova@gmail.com</a> | +420 728 120 498'));


        $frm->addSelect('tshirtSize', 'Velikost případného trička', Serviceteam::TSHIRT_SIZES)
            ->setDefaultValue($this->person->getTshirtSize())
            ->setOption('description', 'Tričko zatím bohužel úplně nemůžeme slíbit. Nicméně pravděpodobně bude :)');

        $frm->addCroppie('avatar', 'Fotka')
            ->setImageUrl($this->person->getAvatar() ? $this->imageService->getImageUrl($this->person->getAvatar()) : null)
            ->setEmptyImageUrl($this->imageService->getImageUrl($this->person->isMale() ? 'avatar_boy.jpg' : 'avatar_girl.jpg'))
            ->setDefaultValue($this->person->getAvatarCrop() ?: null);

		$frm->addTextArea('note', 'Poznámka')
			->setDefaultValue($this->person->getNote())
			->setOption('description', 'Chceš nám něco vzkázat? Jsi už domluvený k někomu do týmu?');




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
//		$values->departureDate = $values->departureDate ? new \DateTimeImmutable($values->departureDate) : null;

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
