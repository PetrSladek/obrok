<?php

namespace App\Module\Front\Participants\Presenters;

use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
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

//        if (!$this->openRegistrationProgram)
//        {
//            $this->error('Registrace programů je uzavřená');
//        }
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


		// Krinspiro
		$conn = $this->em->getConnection();
		$priorites = $conn->fetchAll('SELECT program_id FROM krinspiro WHERE participant_id = ? ORDER BY priority ASC', [$this->me->getId()]);
		$myKrinspiro = [];
		foreach ($priorites as $priority)
		{
			$myKrinspiro[] = $this->programs->getReference($priority['program_id']);
		}
		$this->template->myKrinspiro = $myKrinspiro;
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
		if (!$this->openRegistrationProgram)
		{
			$this->error('Registrace programů je uzavřená');
		}

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
		if (!$this->openRegistrationProgram)
		{
			$this->error('Registrace programů je uzavřená');
		}

		/** @var Program $program */
		$program = $this->programs->find($programId);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		try
		{
//			// ma uz zaregistrovana 3 vapra, ale zadne z nich neni sport
//			if ($program->isVapro())
//			{
//				$vapros = 0;
//				$sports = 0;
//
//				$sportSection = null;
//				foreach ($this->me->getPrograms() as $otherProgram)
//				{
//					if ($otherProgram->section->getId() === $program->section->getId())
//					{
//						continue;
//					}
//
//					if ($otherProgram->isVapro() && !$otherProgram->isSport())
//					{
//						$vapros++;
//					}
//					elseif ($otherProgram->isSport())
//					{
//						$sports++;
//						$sportSection = $otherProgram->section;
//					}
//				}
//
//				if ($program->isSport() && $sports >= 1)
//				{
//					throw new InvalidStateException('Netradiční Sport můžeš mít jen v jednom VaPro bloku! Nejprve ho odregistruj z ' . $sportSection->title . ' ' . $sportSection->subTitle);
//				}
//
//				if (!$program->isSport() && $vapros >= 3)
//				{
//					throw new InvalidStateException('Můžeš mít jen 3 bloky běžného VaPro. 1 blok musí být Netradiční sport!');
//				}
//			}

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

	/**
	 * Přidání programu učastníkovi
	 *
	 * @param int $programId
	 */
	public function handleAppendProgram($programId)
	{
		if (!$this->openRegistrationProgram)
		{
			$this->error('Registrace programů je uzavřená');
		}

		/** @var Program $program */
		$program = $this->programs->getReference($programId);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		if ($program->section->getId() !== ProgramSection::KRINSPIRO)
		{
			$this->error('Tohle jde jen z Krinspirem!');
		}

		try
		{
			$otherPrograms = $this->me->getProgramsInSection($program->section);

			if (count($otherPrograms) >= 20)
			{
				throw new InvalidStateException("V Krinspiru můžete mít jen 20 aktivit.");
			}

			;

			// Krinspiro priority
			$conn = $this->em->getConnection();
			$conn->insert('krinspiro', [
				'participant_id' => (int) $this->me->getId(),
				'program_id' => (int) $programId,
				'priority' => (int) time(),
			]);

			$this->me->appendProgram($program);
			$this->em->flush();
		}
		catch (InvalidStateException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}

	/**
	 * Odebrání programu učastníkovi
	 *
	 * @param $programId
	 */
	public function handleUnappendProgram($programId)
	{
		if (!$this->openRegistrationProgram)
		{
			$this->error('Registrace programů je uzavřená');
		}

		/** @var Program $program */
		$program = $this->programs->getReference($programId);
		if (!$program)
		{
			$this->error('Program neexistuje');
		}

		try
		{
			// Krinspiro priority
			$conn = $this->em->getConnection();
			$conn->delete('krinspiro', [
				'participant_id' => (int) $this->me->getId(),
				'program_id' => (int) $programId,
			]);

			$this->me->unattendeeProgram($program);
			$this->em->flush();
		}
		catch (InvalidStateException $e)
		{
			$this->flashMessage($e->getMessage(), 'danger');
		}

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


	/**
	 * Seřadí krinspira
	 *
	 * @param array $positions
	 */
	public function handleSort(array $positions)
	{
		if (!$this->openRegistrationProgram)
		{
			$this->error('Registrace programů je uzavřená');
		}

		$myKrinspiro = [];

		foreach ($this->me->getPrograms() as $program)
		{
			if ($program->section->getId() === ProgramSection::KRINSPIRO) // krinspiro
			{
				$myKrinspiro[] = $program->getId();
			}
		}

		foreach ($positions as $position => $programId)
		{
			if (!in_array($programId, $myKrinspiro))
			{
				$this->error('Při řazení nelze přidat novou aktivitu');
			}

			// Krinspiro priority
			$conn = $this->em->getConnection();
			$conn->update('krinspiro', [
				'priority' => (int) $position,
			], [
				'participant_id' => (int) $this->me->getId(),
				'program_id' => (int) $programId,
			]);
		}

		$this->isAjax() ? $this->redrawControl() : $this->redirect('this');
	}


}
