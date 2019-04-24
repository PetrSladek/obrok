<?php

namespace App\Module\Base\Presenters;

use App\Model\Entity\Participant;
use App\Model\Repositories\ParticipantsRepository;
use App\Settings;
use Kdyby\Doctrine\EntityManager;
use App\Services\EmailsService;
use App\Services\ImageService;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;
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

	/** @var Settings @inject */
	public $settings;

    /** @var ParticipantsRepository @inject */
    public $participants;

	/**
	 * Je povoleno registrovat nové učastníky?
	 * @var bool
	 */
	protected $openRegistrationParticipants;

    /**
     * Je povoleno registrovat nové Servisaky?
     * @var bool
     */
	protected $openRegistrationServiceteam;

    /**
     * Je povoleno registrovat program?
     * @var bool
     */
    protected $openRegistrationProgram;

	/**
	 * Datum registrace programů od
	 * @var DateTime|null
	 */
	protected $programRegistrationDateFrom;

    /**
     * Maximální počet registrovaných účastníků
     *
     * @var int
     */
    protected $participantsCapacity;


	/** @var ImageService @inject */
	public $imageService;


	const OPEN_PARTICIPANTS_REGISTRATION_KEY = 'openRegistrationParticipants';

    const OPEN_PROGRAM_REGISTRATION_KEY = 'openProgramParticipants';

	const OPEN_SERVICETEAM_REGISTRATION_KEY = 'openRegistrationServiceteam';

    const CAPACITY_PARTICIPANTS = 'capacityParticipants';

	const PROGRAM_REGISTRATION_DATE_FROM = 'programRegistrationDateFrom';


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
        $this->template->storageUrl = $this->config->storageUrl;

		// Sablona pro emaily
		$this->emails->setTemplate($this->createTemplate());

		$this->ageInDate = DateTime::from('2019-06-07');
		$this->template->ageInDate = $this->ageInDate;

        $this->participantsCapacity = (int) $this->settings->get(self::CAPACITY_PARTICIPANTS, 900); // default 900

		$programRegistrationDateFrom = $this->settings->get(self::PROGRAM_REGISTRATION_DATE_FROM, null);
        $this->programRegistrationDateFrom = $programRegistrationDateFrom ? unserialize($programRegistrationDateFrom) : null;

        $this->openRegistrationParticipants = (bool) $this->settings->get(self::OPEN_PARTICIPANTS_REGISTRATION_KEY, true) && $this->isFreeCapacity(); // default TRUE
        $this->openRegistrationServiceteam = (bool) $this->settings->get(self::OPEN_SERVICETEAM_REGISTRATION_KEY, true); // default TRUE
        $this->openRegistrationProgram = (bool) $this->settings->get(self::OPEN_PROGRAM_REGISTRATION_KEY, false) && $this->isTimeForProgramRegistration(); // default FALSE
	}

	/**
	 * Je jeste volna kapacita pro ucastniky?
	 *
	 * @return bool
	 */
	private function isFreeCapacity()
	{
		$countParticipants = $this->participants->countBy(['confirmed'=>true]);
		return max(0, $this->participantsCapacity - $countParticipants) > 0;
	}


	/**
	 * Nastal čas pro registraci programů
	 *
	 * @return bool
	 */
	private function isTimeForProgramRegistration()
	{
		if (!$this->programRegistrationDateFrom)
		{
			return false;
		}

		$now = new DateTime();
		return $now >= $this->programRegistrationDateFrom;
	}


	/**
	 * @inheritdoc
	 */
	public function beforeRender()
	{
		// Variables
//        $this->template->template = $this->template;
		$this->template->config = $this->config;
		$this->template->storageUrl = $this->config->storageUrl;
		$this->template->user = $this->getUser();

        $this->template->participantsCapacity = $this->participantsCapacity;
        $this->template->openRegistrationProgram = $this->openRegistrationProgram;
		$this->template->programRegistrationDateFrom = $this->programRegistrationDateFrom;
        $this->template->openRegistrationServiceteam = $this->openRegistrationServiceteam;
        $this->template->openRegistrationParticipants = $this->openRegistrationParticipants;
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
