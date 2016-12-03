<?php

namespace App\Forms;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Services\ImageService;
use Brabijan\Images\ImageStorage;
use Doctrine\ORM\EntityManager;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;

class GroupForm extends Control
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
	 * @var ParticipantsRepository
	 */
	private $participants;

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
	 * @var ImageStorage
	 */
	private $imageService;


	/**
	 * ServiceteamRegistrationForm constructor.
	 *
	 * @param EntityManager $em
	 * @param ImageService  $imageService
	 * @param int           $id
	 */
	public function __construct(EntityManager $em, ImageService $imageService, $id)
	{
		parent::__construct();

		$this->em = $em;
		$this->imageService = $imageService;

		$this->participants = $this->em->getRepository(Participant::class);
		$this->groups = $this->em->getRepository(Group::class);;
		$this->group = $this->groups->find($id);

		$this->ageInDate = new DateTime('now');
	}


	/**
	 * Vykreslí komponentu s formulářem
	 */
	public function render()
	{
		$this['form']->render();
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
	 * @return Form
	 */
	public function createComponentForm()
	{

		$frm = new Form();

		$frm->addGroup('Základní informace');
		$frm->addText('name', 'Název skupiny')
			->setDefaultValue($this->group->name)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setAttribute('title', 'Název skupiny, pod kterým budete vystupovat (např. RK Másla)')
			->setAttribute('data-placement', 'right');
		$frm->addText('city', 'Město')
			->setDefaultValue($this->group->city)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setAttribute('title', 'Město, ke kterému se skupina "hlásí", ve kterém funguje')
			->setAttribute('data-placement', 'right');
		$frm->addTextarea('note', 'O skupině')
			->setDefaultValue($this->group->note)
			->addRule(Form::FILLED, 'Zapoměl(a) jsi zadat %label')
			->setAttribute('class', 'input-xxlarge')
			->setAttribute('title', 'Napišt nám krátkou charakteristku vaší skupiny. Jste fungující kmen nebo skupina "jednotlivců"? Čím se zabýváte? Napiště něco, co by ostatní mohlo zajímat!')
			->setAttribute('data-placement', 'right');

		$frm->addSelect('boss', 'Vedoucí skupiny (18+)', $this->group->getPossibleBosses($this->ageInDate))
			->setDefaultValue($this->group->boss ? $this->group->boss->id : null)
			->setPrompt('- Vyberte vedoucího skupiny -');
//            ->addCondition(Form::FILLED)
//                ->addRule(callback('Participant','validateBossAge'), 'Věk vedoucího skupiny Obroku 2015 musí být 18 let nebo více');

		$frm->addGroup('Doplňující údaje');

		$frm->addCroppie('avatar', 'Obrázek / znak skupiny')
			->setImageUrl($this->group->getAvatar() ? $this->imageService->getImageUrl($this->group->getAvatar()) : null)
			->setEmptyImageUrl($this->imageService->getImageUrl('avatar_group.jpg'))
			->setDefaultValue($this->group->getAvatarCrop() ?: null);

//		$frm->addGpsPicker('location', 'Mapa roverských kmenů:', [
//			'zoom' => 11,
//			'size' => [
//				'x' => '100%',
//				'y' => '400',
//			]])
//			->setDefaultValue($this->group->locationLng !== null && $this->group->locationLng !== null ? [$this->group->locationLat, $this->group->locationLng] : null)
//			->setOption('description', 'Píchněte špendlík vašeho kmene do mapy a pomozte tím vytvoření Mapy českého roveringu');

		$frm->addSubmit('send', 'Uložit údaje skupiny')
			->setAttribute('class', 'btn btn-primary');

		$frm->onSuccess[] = $this->processForm;

		return $frm;
	}


	public function processForm(Form $_, $values)
	{

		$values->locationLat = $values->location->lat;
		$values->locationLng = $values->location->lng;
		unset($values->location);

		/** @var \Croppie $avatar */
		$avatar = $values->avatar;
		unset($values->avatar);

		if ($avatar)
		{
			if ($image = $avatar->getFileUpload())
			{
				$filename = $this->imageService->upload($image);
				$this->group->setAvatar($filename);
			}

			$this->group->setAvatarCrop($avatar->getCrop());
		}
		else
		{
			$this->group->removeAvatar();
		}

		foreach ($values as $key => $value)
		{
			if ($key == 'boss' && $value)
			{
				$value = $this->participants->find($value);
			} // najdu entitu bosse

			$this->group->$key = $value;
		}

		$this->em->flush();
		$this->onSave($this, $this->group);
	}

}


/**
 * Interface IGroupFormFactory
 * @package App\Forms
 */
interface IGroupFormFactory
{
	/** @return GroupForm */
	function create($id);
}