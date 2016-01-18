<?php

namespace App\Module\Base\Presenters;

use App\Model\Phone;
use App\Model\Repositories\SettingsRepository;
use Kdyby\Doctrine\EntityManager;
use App\Services\EmailsService;
use App\Services\ImageService;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
use Nette\Utils\Html;
use PetrSladek\SkautIS\SkautIS;

/**
 * Class BasePresenter
 * @package App\Module\Base\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
abstract class BasePresenter extends \Nette\Application\UI\Presenter
{

	/** @var ArrayHash */
	protected $config;

	/** @var ImageService @inject */
	public $images;

	/** @var EmailsService @inject */
	public $emails;

	/** @var EntityManager @inject */
	public $em;

	public $ageInDate;

	/** @var SkautIS @inject */
	public $skautis;

	/** @var SettingsRepository @inject */
	public $settings;

	/**
	 * Je povoleno registrovat nové učastníky?
	 * @var bool
	 */
	public $openRegistrationParticipants;

	/**
	 * Je povoleno registrovat nové Servisaky?
	 * @var bool
	 */
	public $openRegistrationServiceteam;

	const OPEN_PARTICIPANTS_REGISTRATION_KEY = 'openRegistrationParticipants';

	const OPEN_SERVICETEAM_REGISTRATION_KEY = 'openRegistrationServiceteam';


	/**
	 * @inheritdoc
	 */
	protected function startup()
	{
		parent::startup();

		$this->config = ArrayHash::from($this->context->parameters['app']);

		// Sablona pro emaily
		$this->emails->setTemplate($this->createTemplate());

		$this->ageInDate = DateTime::from('8.6.2015');
		$this->template->ageInDate = $this->ageInDate;

		$this->openRegistrationParticipants = $this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true); // default TRUE
		$this->template->openRegistrationParticipants = $this->openRegistrationParticipants;

		$this->openRegistrationServiceteam = $this->settings->get(self::OPEN_SERVICETEAM_REGISTRATION_KEY, true); // default TRUE
		$this->template->openRegistrationServiceteam = $this->openRegistrationServiceteam;
	}


	/**
	 * @inheritdoc
	 */
	public function beforeRender()
	{
		// Variables
		$this->template->config = $this->config;
		$this->template->storageUrl = $this->config->storageUrl;

		$this->template->user = $this->getUser();

	}


	/**
	 * Flashzpráva s formátovaným obsahem
	 *
	 * @param string  $type
	 * @param string $format
	 * @param        $args
	 *
	 * @return \stdClass
	 */
	public function flashMessageFormatted($type, $format, $args = null)
	{
		$args = func_get_args();
		array_shift($args);
		array_shift($args);

		return $this->flashMessage(vsprintf($format, $args), $type);
	}


	/**
	 * Odhlášení uživatele
	 */
	public function handleLogout()
	{

		$this->getUser()->logout();

		$this->flashMessage("Odhlášení proběhlo úspěšně");
		$this->redirect('this');
	}

}
