<?php
/**
 * Ucastnicke skupiny - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;
use Nette\NotImplementedException;

/**
 * @Entity(repositoryClass="App\Model\Repositories\ProgramsRepository")
 *
 * @property string        $name
 * @property string        $lector
 * @property string        $perex
 *
 * @property \DateTime     $start
 * @property \DateTime     $end
 *
 * @property string        $tools
 * @property string        $location
 *
 * @property string        $capacity
 * @property Participant[] $attendees
 *
 * @property ProgramSection $section
 */
class Program
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	use \Kdyby\Doctrine\Entities\MagicAccessors;

	/**
	 * Nazev programu
	 * @Column(type="string", length=255)
	 */
	protected $name;

	/**
	 * Přednášející / Pořádající / Lektor
	 * @Column(type="string", length=255)
	 */
	protected $lector;

	/**
	 * Perex
	 * @Column(type="text", nullable=true)
	 */
	protected $perex;

	/**
	 * Start programu
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $start;

	/**
	 * Konec programu
	 * @Column(name="`end`", type="datetime")
	 * @var \DateTime
	 */
	protected $end;

	/**
	 * Pomucky a potreby
	 * @Column(type="text", nullable=true)
	 */
	protected $tools;

	/**
	 * Umístění (v rámci tábořiště nebo města)
	 * @Column(type="text", nullable=true)
	 */
	protected $location;

	/**
	 * Kapacita
	 * @Column(type="integer")
	 */
	protected $capacity;

	/**
	 * @ManyToMany(targetEntity="Participant", inversedBy="programs", cascade={"persist"})
	 * @var ArrayCollection
	 */
	protected $attendees;

	/**
	 * Kategorie (Blok) do ktereho program patri
	 * @ManyToOne(targetEntity="ProgramSection", inversedBy="programs", cascade={"persist"})
	 * @JoinColumn(name="section_id", referencedColumnName="id")
	 *
	 * @var ProgramSection
	 **/
	protected $section;


	/**
	 * Program constructor.
	 */
	public function __construct()
	{
		$this->attendees = new ArrayCollection();
	}


	/**
	 * @return mixed
	 */
	public function getOccupied()
	{
		return $this->attendees->count();
	}


	/**
	 * Je program už zaplněný?
	 * @return bool
	 */
	public function isFull()
	{
		return $this->getOccupied() >= $this->capacity;
	}

	/**
	 * @param Participant $participant
	 *
	 * @return $this
	 */
	public function addAttendee(Participant $participant)
	{
		$this->attendees->add($participant);

		return $this;
	}


	/**
	 * @param Participant $participant
	 *
	 * @return $this
	 */
	public function removeAttedee(Participant $participant)
	{
		$this->attendees->removeElement($participant);

		return $this;
	}


//	/**
//	 * Je program vapro?
//	 *
//	 * @return int
//	 */
//	public function isVapro()
//	{
////		return preg_match('/vapro/i', $this->section->title);
//
//		return in_array($this->section->getId(), [3, 4, 5, 6]);
//
////		return true;
//	}

//	/**
//	 * Je program sport?
//	 *
//	 * @return int
//	 */
//	public function isSport()
//	{
//		return $this->isVapro() && preg_match('/sport/i', $this->name);
//	}

}