<?php

namespace App\Forms;

use App\Hydrators\SkautisHydrator;
use App\Model\Entity\Group;
use App\Model\Entity\Person;
use App\Model\Entity\UnspecifiedPerson;
use App\Model\Repositories\PersonsRepository;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

class GroupRegistrationForm extends Control
{

	/**
	 * @var callable[]
	 */
	public $onGroupRegistered;

	/**
	 * @var EntityManager
	 */
	private $em;


	/**
	 * GroupRegistrationForm constructor.
	 */
	public function __construct(EntityManager $em)
	{
		parent::__construct();
		$this->em = $em;
	}


	/**
	 * Vykreslí formulář
	 */
	public function render()
	{
		$this['form']->render();
	}


	/**
	 * @return Form
	 */
	public function createComponentForm()
	{

		$frm = new Form();

		$frm->addGroup('Informace o skupině');
		$frm->addText('name', 'Název skupiny')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setOption('description', 'Název skupiny, pod kterým budete vystupovat (např. RK Másla)');
		$frm->addText('city', 'Město')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setOption('description', 'Město, ke kterému se skupina "hlásí", ve kterém funguje');
		$frm->addTextarea('note', 'O skupině')
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setOption('description', 'Napište nám krátkou charakteristku vaší skupiny. Jste fungující kmen nebo skupina "jednotlivců"? Čím se zabýváte? Napiště něco, co by ostatní mohlo zajímat!');

		$frm->addGpsPicker('location', 'Mapa roverských kmenů:', [
			'zoom' => 11,
			'size' => [
				'x' => '100%',
				'y' => '400',
			]])
			->setOption('description', 'Píchnete špendlík vašho kmene do mapy, a pomozte tím vytvoření Mapy českého roveringu');

		$frm->addSubmit('send', 'Zaregistrovat Skupinu na Obrok 2015')
			->setAttribute('class', 'btn btn-primary btn-block');

		$frm->onSuccess[] = $this->processForm;

		return $frm;
	}


	/**
	 * Zpracování formuláře
	 * @param $form
	 * @param $values
	 */
	public function processForm($form, $values)
	{

		$group = new Group();
		$group->name = $values->name;
		$group->city = $values->city;
		$group->note = $values->note;

		$group->locationLat = $values->location->lat;
		$group->locationLng = $values->location->lng;

		$this->em->persist($group);
		$this->em->flush();

		$this->onGroupRegistered($this, $group);
	}

}


/**
 * Interface IGroupRegistrationFormFactory
 * @package App\Forms
 */
interface IGroupRegistrationFormFactory
{
	/** @return GroupRegistrationForm */
	function create();
}