<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Program;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use App\Query\ProgramsSectionsQuery;
use Nette\InvalidStateException;

/**
 * Class ProgramPresenter
 * @package App\Module\Front\Participants\Presenters
 * @author  psl <petr.sladek@webnode.com>
 */
class ProgramPresenter extends ParticipantAuthBasePresenter
{

	/** @var ProgramsRepository @inject */
	public $programs;

	/** @var ProgramsSectionsRepository @inject */
	public $sections;

	/**
	 * Připravý data pro vypsání výchozí šablony
	 */
	public function renderDefault()
	{
		$this->template->sections = $this->sections->fetch((new ProgramsSectionsQuery())->withPrograms());
	}


	/**
	 * Příkaz smazání programu učastníkovi
	 *
	 * @param $programId
	 *
	 * @throws \Nette\Application\BadRequestException
	 */
	public function handleUnattendeeProgram($programId)
	{
		/** @var Program $program */
		$program = $this->programs->find($programId);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		$this->me->unattendeeProgram($program);
		$this->em->flush();

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Přidání programu učastníkovi
	 *
	 * @param $programId
	 */
	public function handleAttendeeProgram($programId)
	{
		/** @var Program $program */
		$program = $this->programs->find($programId);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}


		try
		{
			if ($this->me->hasOtherProgramInTime($program))
			{
				$otherProgram = $this->me->findOtherProgramInTime($program);
				$this->me->unattendeeProgram($otherProgram);
			}

			if ($this->me->hasOtherProgramInSection($program))
			{
				$otherProgram = $this->me->findOtherProgramInSection($program);
				$this->me->unattendeeProgram($otherProgram);
			}

			$this->me->attendeeProgram($program);
			$this->em->flush();
		}
		catch (InvalidStateException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}

}
