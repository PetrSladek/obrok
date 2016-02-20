<?php

namespace App\Forms;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Person;
use App\Model\Entity\Serviceteam;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\PersonsRepository;
use App\Model\Repositories\ServiceteamRepository;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class ParticipantRegistrationForm extends Control
{

	/**
	 * @var callable[]
	 */
	public $onParticipantRegistred;

	/**
	 * @var EntityManager
	 */
	private $em;

	/**
	 * @var PersonsRepository
	 */
	private $persons;

	/**
	 * @var Person|Participant
	 */
	private $person;

	/**
	 * @var GroupsRepository
	 */
	private $groups;

	/**
	 * @var Group
	 */
	private $group;

	/**
	 * @var DateTime
	 */
	private $ageInDate;


	/**
	 * @param EntityManager $em
	 * @param int           $id      ID Osoby ze ktere chceme udelat ucastnika
	 * @param int           $groupId ID Skupiny do ktere cheme dat ucastnika
	 */
	public function __construct(EntityManager $em, $id, $groupId)
	{
		parent::__construct();
		$this->persons = $em->getRepository(Person::class);
		$this->person = $this->persons->find($id);
		if (!$this->person)
		{
			throw new \InvalidArgumentException("Person #$id not found");
		}

		$this->groups = $em->getRepository(Group::class);
		$this->group = $this->groups->find($groupId);
		if (!$this->group)
		{
			throw new \InvalidArgumentException("Group #$id not found");
		}

		$this->em = $em;

		$this->ageInDate = new DateTime('now');
	}


	/**
	 * @return DateTime
	 */
	public function getAgeInDate()
	{
		return $this->ageInDate;
	}


	/**
	 * @param DateTime $ageInDate
	 */
	public function setAgeInDate($ageInDate)
	{
		$this->ageInDate = $ageInDate;
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

		$frm->addGroup('Osobní informace');

		$frm->addText('firstName', 'Jméno')
			->setDisabled()
			->setDefaultValue($this->person->firstName);
		$frm->addText('lastName', 'Příjmení')
			->setDisabled()
			->setDefaultValue($this->person->lastName);

		$frm->addText('nickName', 'Přezdívka')
			->setDefaultValue($this->person->nickName);

		$frm->addDatepicker('birthdate', 'Datum narození:')
			->setDefaultValue($this->person->birthdate)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Datum narození nebo je ve špatném formátu (musí být dd.mm.yyyy)')
			->addRule(Form::RANGE, 'Podle data narození vám 1.6.2015 ještě nebude 15 let (což porušuje podmínky účasti)', array(null, DateTime::from('1.6.2015')->modify('-15 years')))
			->addRule(Form::RANGE, 'Podle data narození vám 10.6.2015 bude už více než 25 let (což porušuje podmínky účasti)', array(DateTime::from('10.6.2015')->modify('-25 years'), null));

		$frm->addRadioList('gender', 'Pohlaví', [Person::GENDER_MALE => 'muž', Person::GENDER_FEMALE => 'žena'])
			->setDefaultValue($this->person->gender)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Trvalé bydliště');
		$frm->addText('addressStreet', 'Ulice a čp.')
			->setDefaultValue($this->person->addressStreet)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressCity', 'Město')
			->setDefaultValue($this->person->addressCity)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');
		$frm->addText('addressPostcode', 'PSČ')
			->setDefaultValue($this->person->addressPostcode)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label');

		$frm->addGroup('Kontaktní údaje');
		$frm->addText('phone', 'Mobilní telefon')
			->setDefaultValue($this->person->phone)
			->setEmptyValue('+420')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat Mobilní telefon')
			->addRule([$frm, 'isPhoneNumber'], 'Telefonní číslo je ve špatném formátu')
			->setAttribute('description', 'Mobilní telefon, na kterém budeš k zastižení během celé akce');
//        $frm->addCheckbox('phoneIsSts', 'je v STS?')
//            ->setAttribute('description','Je toto telefoní číslo v Skautské telefoní síti?');
		$frm->addText('email', 'E-mail:')
			->setDefaultValue($this->person->email)
			->addRule(Form::FILLED, 'Zadejte E-mail')
			->setOption('description', 'Tvůj email na který ti budou chodit informace');

		$frm->addGroup('Zdravotní omezení');
		$frm->addTextarea('health', 'Zdravotní omezení a alergie')
			->setDefaultValue($this->person->health);

		$frm->addGroup(null);
		$frm->addCheckbox('conditions', Html::el()->setHtml('Souhlasím s <a target="_blank" href="http://www.obrok15.cz/registrace/">podmínkami účasti na akci</a> a s <a target="_blank" href="http://www.obrok15.cz/obecna-ustanoveni-storno-podminky/">obecnými ustanoveními</a>'))
			->addRule($frm::FILLED, 'Musíte souhlasit s podmínkami účasti')
			->setOmitted();

		$frm->addSubmit('send', 'Zaregistrovat se')
			->setAttribute('class', 'btn btn-primary btn-block');

		$frm->onSuccess[] = $this->processForm;

		return $frm;
	}


	/**
	 * Zpracování formuláře
	 *
	 * @param Form $form
	 * @param      $values
	 */
	public function processForm(Form $form, $values)
	{

		// pretypujeme osobu na Participant
		$this->persons->changePersonTypeTo($this->person, Person::TYPE_PARTICIPANT);

		foreach ($values as $key => $value)
		{
			$this->person->$key = $value;
		}

		// pridame ho do skupiny
		$this->group->addParticipant($this->person);

		// pokud skupina nema admina, udelaho z nej
		if (!$this->group->hasAdmin())
		{
			$this->person->setAdmin();
		}

		// Pokud skupina nema sefa a tomuhle ucastnikovi je nad 18, tak z nej udaleme sefa
		if (!$this->group->hasBoss() && $this->person->getAge($this->ageInDate) >= 18)
		{
			$this->group->setBoss($this->person);
		}

		// skupina i uzivatel jsou v DB tak se nemusi persistovat
		$this->em->flush();

		$this->onParticipantRegistred($this, $this->person, $this->group);
	}

}


/**
 * Interface IParticipantRegistrationFormFactory
 * @package App\Forms
 */
interface IParticipantRegistrationFormFactory
{
	/** @return ParticipantRegistrationForm */
	function create($id, $groupId);
}