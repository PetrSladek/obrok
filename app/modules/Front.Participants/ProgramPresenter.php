<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Program;
use App\Model\Repositories\ProgramsRepository;
use App\Model\Repositories\ProgramsSectionsRepository;
use App\Query\ProgramsSectionsQuery;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\Internal\Hydration\ArrayHydrator;
use Doctrine\ORM\Query\ResultSetMapping;
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

    public function startup()
    {
        parent::startup();

        if (!$this->openRegistrationProgram)
        {
            $this->error('Registrace programů je uzavřená');
        }
    }


    /**
	 * Připravý data pro vypsání výchozí šablony
	 */
	public function renderDefault()
	{
		$query = (new ProgramsSectionsQuery())
			->withPrograms()
			->withAttendies();
		$sections = $this->sections->fetch($query);

		$this->template->sections = $sections;
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
			// ma uz zaregistrovana 3 vapra, ale zadne z nich neni sport
			if ($program->isVapro())
			{
				$vapros = 0;
				$sports = 0;
				foreach ($this->me->getPrograms() as $otherProgram)
				{
					if ($otherProgram->section->getId() === $program->section->getId())
					{
						continue;
					}

					if ($otherProgram->isVapro() && !$otherProgram->isSport())
					{
						$vapros++;
					}
					elseif ($otherProgram->isSport())
					{
						$sports++;
					}
				}

				if ($program->isSport() && $sports >= 1)
				{
					throw new InvalidStateException('Netradiční sport můžeš mít jen v 1 bloku VaPro!');
				}

				if (!$program->isSport() && $vapros >= 3)
				{
					throw new InvalidStateException('Můžeš mít jen 3 bloky běžného VaPro. 1 blok musí být Netradiční sport!');
				}
			}

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
