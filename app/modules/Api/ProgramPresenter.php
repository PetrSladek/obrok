<?php

namespace App\Module\Api\Presenters;

use App\Forms\GroupForm;
use App\Forms\IGroupFormFactory;
use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Forms\Form;
use App\Model\Entity\Person;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use App\Model\Repositories\GroupsRepository;
use App\Model\Repositories\ParticipantsRepository;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use App\Query\ParticipantsQuery;
use App\Query\ProgramsQuery;
use App\Query\ProgramsSectionsQuery;
use App\Services\ImageService;
use Nette\Utils\AssertionException;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

/**
 * Class ProgramPresenter
 * @package App\Module\Api\Presenters
 */
class ProgramPresenter extends \Nette\Application\UI\Presenter
{

    /**
     * @var ParticipantsRepository
     * @inject
     */
    public $participantRepostitory;

    /**
     * @var ProgramsRepository
     * @inject
     */
    public $programRepository;

    /**
     * @var ProgramsSectionsRepository
     * @inject
     */
    public $sectionRepository;

    /**
     * Připravý data pro vypsání výchozí šablony
     *
     * @param null $sectionId
     * @throws \Nette\Application\AbortException
     */
	public function actionDefault($sectionId = null)
	{
	    $query = new ProgramsQuery();

	    // vytahnu vsechny sekce, aby pak nebyli jako proxy objecty
	    $sections = $this->sectionRepository->fetch(new ProgramsSectionsQuery());
	    $sections = $sections->toArray();

	    if ($sectionId)
	    {
            $query->inSections($sectionId);
        }

        $programs = $this->programRepository->fetch($query);
	    $programs->applySorting(['p.start' => 'ASC']);
        $programs = $programs->toArray();

        $this->sendJson($programs);
	}

    /**
     * Vrátí programy podle skautis User ID
     * @param $id
     * @param null $sectionId
     * @throws \Nette\Application\AbortException
     */
	public function actionParticipant($id, $sectionId = null)
    {
        $query = new ProgramsQuery();
        $query->forAttendee($id);

        // vytahnu vsechny sekce, aby pak nebyli jako proxy objecty
        $sections = $this->sectionRepository->fetch(new ProgramsSectionsQuery());
        $sections = $sections->toArray();

        if ($sectionId)
        {
            $query->inSections($sectionId);
        }

        $programs = $this->programRepository->fetch($query);
        $programs = $programs->toArray();

        $this->sendJson($programs);

    }

    /**
     * Vrátí programy podle skautis User ID
     * @param $id
     * @param null $sectionId
     * @throws \Nette\Application\AbortException
     */
    public function actionSkautisUser($id, $sectionId = null)
    {
        $query = new ParticipantsQuery();
        $query->bySkautisUserId($id);

        /** @var Participant|null $participant */
        $participant = $this->participantRepostitory->fetchOne($query);

        if ($participant)
        {
            $this->actionParticipant($participant->getId(), $sectionId);
        }
        else
        {
            $this->sendJson([]);
        }
    }

    /**
     * @throws \Nette\Application\AbortException
     */
    public function actionSections()
    {
        // vytahnu vsechny sekce, aby pak nebyli jako proxy objecty
        $sections = $this->sectionRepository->fetch(new ProgramsSectionsQuery());
        $sections = $sections->toArray();

        $this->sendJson($sections);
    }
}
