<?php

namespace App\Forms;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Person;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Repositories\PersonsRepository;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class ServiceteamRegistrationForm extends Control
{

	/**
	 * @var array of function($this, $person)
	 */
	public $onServiceteamRegistered;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var PersonsRepository
	 */
	private $persons;

	/**
	 * @var UnspecifiedPerson
	 */
	private $person;


	/**
	 * ServiceteamRegistrationForm constructor.
	 *
	 * @param PersonsRepository $persons
	 * @param int               $id
	 */
	public function __construct(PersonsRepository $persons, $id)
	{
		parent::__construct();
		$this->persons = $persons;
		$this->person = $this->persons->find($id);
		$this->em = $this->persons->getEntityManager();;
	}


	/**
	 * Vykreslí komponentu s formulářem
	 */
	public function render()
	{
		$this['form']->render();
	}


	/**
	 * Vytvoří fgormulář
	 * @return Form
	 */
	public function createComponentForm()
	{

		$frm = new Form();

		$frm->addGroup('Osobní informace');

		$frm->addText('firstName', 'Jméno')
			->setDisabled()
			->setDefaultValue($this->person->getFirstName());
		$frm->addText('lastName', 'Příjmení')
			->setDisabled()
			->setDefaultValue($this->person->getLastName());
		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->person->getNickName());

		$frm->addDatepicker('birthdate', 'Datum narození')
			->setDefaultValue($this->person->getBirthdate())
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu')
			->addRule(Form::RANGE, 'Podle data narození vám 7.6.2019 ještě nebude 18 let (což porušuje podmínky účasti)', array(null, DateTime::from('7.6.2019')->modify('-18 years')))
			->setAttribute('description', 'Tvoje Datum narození ve formátu dd.mm.yyyy');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->person->getAddressCity())
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Město')
			->setAttribute('description', 'Město, kde aktuálně bydlíš nebo skautuješ');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->person->getPhone())
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			//->addRule(Form::REGEXP,'Telefonní číslo je ve špatném formátu','/^[+0-9. ()-]*$/ui')
			->setAttribute('description', 'Mobilní telefon, na kterém budeš k zastižení během celé akce');

		$frm->addText('email', 'E-mail')
			->setDefaultValue($this->person->getEmail())
			->setEmptyValue('@')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat E-mail')
			->addRule(Form::EMAIL, 'E-mailová adresa není platná')
			->setAttribute('description', 'Kontaktní e-mail na který vám budou chodit informace');

		$frm->addCheckbox('conditions', Html::el()->setHtml('Souhlasím s <a target="_blank" href="http://www.obrok19.cz/registrace/">podmínkami účasti na akci</a> a s <a target="_blank" href="http://www.obrok19.cz/obecna-ustanoveni-storno-podminky/">obecnými ustanoveními</a>'))
			->addRule($frm::FILLED, 'Musíte souhlasit s podmínkami účasti')
			->setOmitted();

		//$frm->addGroup(null);
		$frm->addSubmit('send', 'Registrovat se do servis týmu')
			->setAttribute('class', 'btn btn-primary btn-block');

		$frm->onSuccess[] = [$this, 'processForm'];

		return $frm;
	}


    /**
     * Zpracování formuláře
     *
     * @param Form $frm
     *
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
	public function processForm(Form $frm)
	{
		$values = $frm->getValues();

		// Zmenim na Servisaka
		$this->persons->changePersonTypeTo($this->person, Person::TYPE_SERVICETEAM);
		$this->person->setRegisteredAt(new DateTime());

		foreach ($values as $key => $value)
		{
			$this->person->$key = $value;
		}

		$this->em->flush();

		$this->onServiceteamRegistered($this, $this->person);
	}

}


/**
 * Interface IServiceteamRegistrationFormFactory
 * @package App\Forms
 */
interface IServiceteamRegistrationFormFactory
{
	/** @return ServiceteamRegistrationForm */
	function create($id);
}
