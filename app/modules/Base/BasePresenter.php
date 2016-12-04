<?php

namespace App\Module\Base\Presenters;

use App\Model\Phone;
use App\Model\Repositories\ParticipantsRepository;
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

    /** @var ParticipantsRepository @inject */
    public $participants;

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

    /**
     * Je povoleno registrovat program?
     * @var bool
     */
    public $openRegistrationProgram;

	/** @var ImageService @inject */
	public $imageService;


	const OPEN_PARTICIPANTS_REGISTRATION_KEY = 'openRegistrationParticipants';

    const OPEN_PROGRAM_REGISTRATION_KEY = 'openProgramParticipants';

	const OPEN_SERVICETEAM_REGISTRATION_KEY = 'openRegistrationServiceteam';


	/**
	 * @return \Nette\Application\UI\ITemplate
	 */
	protected function createTemplate()
	{
		$template =  parent::createTemplate();
		$template->imageService = $this->imageService;

		return $template;
	}


	/**
	 * @inheritdoc
	 */
	protected function startup()
	{
		parent::startup();

		$this->config = ArrayHash::from($this->context->parameters['app']);

		// Sablona pro emaily
		$this->emails->setTemplate($this->createTemplate());

		$this->ageInDate = DateTime::from('2017-07-06');
		$this->template->ageInDate = $this->ageInDate;


        $countParticipants = count($this->participants->findBy(['confirmed'=>true]));
        $freeCapacity = max(0, 900 - $countParticipants) > 0;

        // otevřená registrace v settings
        $this->template->openRegistrationParticipantsSettings = $this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true); // default TRUE

		$this->openRegistrationParticipants = $this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true) && $freeCapacity; // default TRUE
		$this->template->openRegistrationParticipants = $this->openRegistrationParticipants;

		$this->openRegistrationServiceteam = $this->settings->get(self::OPEN_SERVICETEAM_REGISTRATION_KEY, true); // default TRUE
		$this->template->openRegistrationServiceteam = $this->openRegistrationServiceteam;

        $this->openRegistrationProgram = $this->settings->get(self::OPEN_PROGRAM_REGISTRATION_KEY, false); // default false
        $this->template->openRegistrationProgram = $this->openRegistrationProgram;
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
