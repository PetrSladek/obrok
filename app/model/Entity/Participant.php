<?php
/**
 * Servisak - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use App\Model\Address;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;
use Nette\InvalidStateException;
use Nette\Utils\DateTime;

/**
 * @Entity(repositoryClass="App\Model\Repositories\ParticipantsRepository")
 * @property bool       $admin
 * @property Group|null $group
 */
class Participant extends Person
{

	/**
	 * Je admin skupiny? (muze editovat/vyhodit cleny atd..)
	 * @var bool
	 * @Column(type="boolean")
	 */
	protected $admin = false;

	// Asociace

	/**
	 * Skupina do ktere patri
	 * @ManyToOne(targetEntity="Group",  inversedBy="participants", cascade={"persist"})
	 * @JoinColumn(name="group_id", referencedColumnName="id")
	 *
	 * @var Group
	 **/
	protected $group;

	/**
	 * @ManyToMany(targetEntity="Program", mappedBy="attendees", cascade={"persist"})
	 * @OrderBy({"start" = "ASC"})
	 * @var Program[]|ArrayCollection
	 */
	protected $programs;


	public function __construct()
	{
		parent::__construct();
		$this->programs = new ArrayCollection();
	}


	/**
	 * @return bool
	 */
	public function isAdmin()
	{
		return $this->admin;
	}


	/**
	 * @param bool $admin
	 */
	public function setAdmin($admin = true)
	{
		$this->admin = $admin;
	}


	/**
	 * @param Group $group
	 */
	public function setGroup(Group $group)
	{
		$this->group = $group;
	}


	/**
	 * @return Group
	 */
	public function getGroup()
	{
		return $this->group;
	}


	/**
	 * @param bool $confirmed
	 *
	 * @return $this
	 */
	public function setConfirmed($confirmed = true)
	{

		if ($confirmed == false)
		{
			$this->confirmed = false;
			$this->paid = false;
			$this->arrived = false;
			$this->left = false;

			if ($this->getGroup()->getBoss() === $this)
			{
				$this->getGroup()->setBoss(null); // zrusime ho jako sefa
				$this->getGroup()->tryDefineBoss(); // zkusime najit jinyho vhodnyho
			}
		}
		else
		{
			$this->confirmed = true;
		}

		return $this;
	}


	/**
	 * @param Program $program
	 *
	 * @return $this
	 */
	public function unattendeeProgram(Program $program)
	{
		$this->programs->removeElement($program);
		$program->removeAttedee($this);

		return $this;
	}


	/**
	 * @param Program $program
	 *
	 * @return $this
	 * @throws InvalidStateException
	 */
	public function attendeeProgram(Program $program)
	{

		if ($program->isFull())
		{
			throw new InvalidStateException('Kapacita programu je již plná.', 10);
		}

		if ($this->isAtendeeProgram($program))
		{
			throw new InvalidStateException('Uživatel je již přihlášen na tento program.', 20);
		}

		if ($this->hasOtherProgramInTime($program))
		{
			throw new InvalidStateException("V tuto dobu máte přihlášený již jiný program.", 30);
		}

		if ($this->hasOtherProgramInSection($program))
		{
			throw new InvalidStateException("V teto sekci máte přihlášený již jiný program.", 30);
		}

		$this->programs->add($program);
		$program->addAttendee($this);

		return $this;
	}


	public function isAtendeeProgram(Program $program)
	{
		return $this->programs->contains($program);
	}


	/**
	 * Ma uz jiny program v case tohoto programu?
	 *
	 * @param Program $program
	 *
	 * @return bool
	 */
	public function hasOtherProgramInTime(Program $program)
	{
		foreach ($this->programs as $otherProgram)
		{
			if ($otherProgram->id == $program->id)
			{
				continue;
			}
			if ($otherProgram->start == $program->start)
			{
				return true;
			}
			if ($otherProgram->start > $program->start && $otherProgram->start < $program->end)
			{
				return true;
			}
			if ($otherProgram->end > $program->start && $otherProgram->end < $program->end)
			{
				return true;
			}
			if ($otherProgram->start < $program->start && $otherProgram->end > $program->end)
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Ma uz jiny program ve stejne sekci jako tento program?
	 *
	 * @param Program $program
	 *
	 * @return bool
	 */
	public function hasOtherProgramInSection(Program $program)
	{
		if ($program->section === null)
		{
			return false;
		}

		foreach ($this->programs as $otherProgram)
		{
			if ($program->section === $otherProgram->section)
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Je vedoucí svojí skupiny?
	 * @return bool
	 */
	public function isBoss()
	{
		return $this->group && $this->group->isBoss($this);
	}

	/**
	 * Vrati objekt s nette identitou
	 */
	public function toIdentity()
	{
		return new \Nette\Security\Identity($this->id, Person::TYPE_PARTICIPANT);
	}

}