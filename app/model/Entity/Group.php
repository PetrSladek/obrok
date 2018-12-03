<?php
/**
 * Ucastnicke skupiny - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use Brabijan\Images\Image;
use Brabijan\Images\ImageProvider;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;

/**
 * @Entity(repositoryClass="App\Model\Repositories\GroupsRepository")
 * @Table(name="groups")
 */
class Group
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	use \Kdyby\Doctrine\Entities\MagicAccessors;
	use Avatarable;

	/**
	 * Datum vytvoreni
	 *
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $createdAt;

	/**
	 * Pocita se s nim i kdyz treba jeste nezaplatili
	 *
	 * @Column(type="boolean")
	 */
	protected $confirmed = true;

	/**
	 * Zaplacená - všichni potvrzení učastníci zaplatili
	 *
	 * @Column(type="boolean")
	 */
	protected $paid = false;

	/**
	 * Přijeli - všichni potvrzeni učastníci přijeli
	 *
	 * @Column(type="boolean")
	 */
	protected $arrived = false;

	/**
	 * Odjeli - odjeli z akce (všichni potvrzené účastníci se deregistrovali
	 * )
	 * @Column(name="`left`", type="boolean")
	 */
	protected $left = false;


//    /**
//     * Variabilni symbol
//     * @Column(type="integer", length=255)
//     */
//    protected $varSymbol;

	/**
	 * Nazev ucastnicke skupiny
	 *
	 * @Column(type="string", length=255)
	 */
	protected $name;

	/**
	 * Mesto
	 *
	 * @Column(type="string", length=255)
	 */
	protected $city;

	/**
	 * Kraj
	 *
	 * @Column(type="string", length=255, nullable=true)
     * @var string
	 */
	protected $region;

	/**
	 * Geolokace LAT
	 *
	 * @Column(type="float", nullable=true)
	 */
	protected $locationLat;

	/**
	 * Geolokace LNG
	 *
	 * @Column(type="float", nullable=true)
	 */
	protected $locationLng;

	/**
	 * Poznamka pri registraci
	 *
	 * @Column(type="text", nullable=true)
	 */
	protected $note;

	/**
	 * Interni poznamka
	 *
	 * @Column(type="text", nullable=true)
	 */
	protected $noteInternal;


	/**
	 * Ucastnici ve skupine
	 *
	 * @OneToMany(targetEntity="Participant", mappedBy="group", cascade={"persist"})
	 * @var Participant[]|ArrayCollection
	 **/
	private $participants;

	/**
	 * Vedouci skupiny (18+)
	 *
	 * @ManyToOne(targetEntity="Participant", cascade={"persist"})
	 * @JoinColumn(name="boss_id", referencedColumnName="id")
	 * @var Participant
	 **/
	private $boss;


	/**
	 * Group constructor.
	 * @param string $name
	 * @param string $city
	 */
	public function __construct($name, $city)
	{
		$this->createdAt = new \DateTime('now');
		$this->participants = new ArrayCollection();
		$this->name = $name;
		$this->city = $city;
	}

	/**
	 * @return mixed
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param mixed $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @return mixed
	 */
	public function getCity()
	{
		return $this->city;
	}

	/**
	 * @param mixed $city
	 */
	public function setCity($city)
	{
		$this->city = $city;
	}

	/**
	 * @return mixed
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @param mixed $note
	 */
	public function setNote($note)
	{
		$this->note = $note;
	}



	/**
	 * @param Participant $boss
	 */
	public function setBoss(Participant $boss = null)
	{
		if ($boss && $boss->getGroup() !== $this)
		{
			throw new \InvalidArgumentException("Vedouci musi byt ze stejne skupiny");
		}

		$this->boss = $boss;
	}


	/**
	 * Pokusi se automaticky priradit vhodneho sefa
	 */
	public function tryDefineBoss()
	{
		if (!$this->hasBoss())
		{
			foreach ($this->getConfirmedParticipants() as $participant)
			{
				if ($participant->getAge() >= 18)
				{
					$this->setBoss($participant);
					break;
				}
			}
		}

		return $this;
	}

	/**
	 * Pokusi se automaticky priradit vhodneho administratora
	 */
	public function tryDefineAdmin()
	{
		if (!$this->hasAdmin())
		{
			if ($this->hasBoss())
			{
				$this->getBoss()->setAdmin(true);
			}
			else
			{
				foreach ($this->getConfirmedParticipants() as $participant)
				{
					$this->setBoss($participant);
					break;
				}
			}
		}

		return $this;
	}


	/**
	 * @return Participant
	 */
	public function getBoss()
	{
		return $this->boss;
	}


	/**
	 * Vrátí účastníky kteřé mohou být šéfem
	 *
	 * @param null $inDate
	 *
	 * @return array
	 */
	public function getPossibleBosses($inDate = null)
	{
		$bosses = [];
		if ($this->boss)
		{
			// Aby byl boss ve vyberu bossu i kdyby uz nejel, nebo mu nekdo zmenil datum narozeni.
			$boss = $this->boss;
			$age = $boss->getAge($inDate);
			$bosses[$this->boss->id] = "{$boss->fullname} (na Obroku {$age} let)";
		}
		foreach ($this->getConfirmedParticipants() as $boss)
		{
			if ($boss->getAge($inDate) >= 18)
			{
				$age = $boss->getAge($inDate);
				$bosses[$boss->id] = "{$boss->fullname} (na Obroku {$age} let)";
			}
		}

		return $bosses;
	}


	/**
	 * Prida ucastnika do teto skupiny
	 *
	 * @param Participant $participant
	 */
	public function addParticipant(Participant $participant)
	{
		$this->participants->add($participant);
		$participant->setGroup($this);

		$this->updateStatus();
	}


	/**
	 * Smaze ucastnika z teto skupiny
	 *
	 * @param Participant $participant
	 */
	public function removeParticipant(Participant $participant)
	{
		if ($participant->getGroup() !== $this)
		{
			throw new \InvalidArgumentException('Ucastnik neni v teto skupine');
		}

		if ($participant->isAdmin())
		{
			$participant->setAdmin(false);
		}

		if ($this->isBoss($participant))
		{
			$this->setBoss(null);
		}

		$participant->clearGroup();

		$this->participants->removeElement($participant);

		$this->updateStatus();
	}


	/**
	 * Je skupina potvrzena ze prijede? (ma alespon jednoho platneho ucastnika)
	 * @return bool
	 */
	public function isConfirmed()
	{
		return (bool) $this->confirmed;
	}


	/**
	 * Je zaplaceno za vsechny platne ucastniky?
	 * @return bool
	 */
	public function isPaid()
	{
		return (bool) $this->paid;
	}


	/**
	 * Odjeli vsichni platni ucastnici z teto skupiny?
	 * @return bool
	 */
	public function isArrived()
	{
		return (bool) $this->arrived;
	}


	/**
	 * Odjeli vsichni platni ucastnici z teto skupiny?
	 * @return bool
	 */
	public function isLeft()
	{
		return (bool) $this->left;
	}

    /**
     * @return mixed
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * @param string $region
     */
    public function setRegion($region)
    {
        $this->region = $region;
    }



	/**
	 * Vrati aktivini ucastniky (se kteryma se pocita ze prijedou)
	 *
	 * @return Participant[]
	 */
	public function getConfirmedParticipants()
	{

		$participants = $this->participants->filter(function(Participant $participant) {
			return $participant->isConfirmed();
		})->toArray();

		return $participants;
	}
  
  	public function getPaidParticipants()
	{

		$participants = $this->participants->filter(function(Participant $participant) {
			return $participant->isPaid();
		})->toArray();

		return $participants;
	}


	/**
	 * Vrati neaktivini ucastniky (kteri neprijedou)
	 *
	 * @return \Doctrine\Common\Collections\Collection|Participant[]
	 */
	public function getUnconfirmedParticipants()
	{
		$participants = $this->participants->filter(function(Participant $participant) {
			return !$participant->isConfirmed();
		})->toArray();

		return $participants;
	}


	/**
	 * Vrati pocet aktivinich ucastniku (se kteryma se pocita ze prijedou)
	 * @return int
	 */
	public function getConfirmedParticipantsCount()
	{
		return count($this->getConfirmedParticipants());
	}
  
  

  	public function getPaidParticipantsCount()
	{
		return count($this->getPaidParticipants());
	}



	/**
	 * Ma skupina nejakho admina
	 * @return bool
	 */
	public function hasAdmin()
	{

		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('admin', true));

		return !$this->participants->matching($criteria)->isEmpty();
	}


	/**
	 * Vrati vsechny administratory skupiny
	 * @return Collection
	 */
	public function getAdministrators()
	{
		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('admin', true));

		return $this->participants->matching($criteria);
	}


	/**
	 * Ma skupina nejakho sefa? (18ti leta zodpovedna osoba)
	 * @return bool
	 */
	public function hasBoss()
	{
		return $this->boss !== null;
	}


	/**
	 * Je zadany ucastnik vedoucím tehle skupiny?
	 * @param Participant $participant
	 *
	 * @return bool
	 */
	public function isBoss(Participant $participant)
	{
		return $this->boss === $participant;
	}

	/**
	 * @return string Vytvori hash pro pozvani ucastnika do skupiny
	 */
	public function getInvitationHash($key)
	{
		$hash = sha1("{$this->createdAt->getTimestamp()}|$key|{$this->id}");

		return substr($hash, 0, 8); // nemusi by tak dlouhy
	}


	/**
     * Vrati variabilni symbol této skupiny
     *
	 * @return int
	 */
	public function getVarSymbol()
	{
		return self::getVarSymbolFromId($this->getId());
	}


	/**
	 * Vrati variabilni symbol skupiny
	 *
	 * @param int $id ID Skupiny
	 *
	 * @return int|null
	 */
	public static function getVarSymbolFromId($id)
	{
		if (empty($id))
		{
			return null;
		}

		// 35100001 - 35199999
        $base = 35100000;
        $max = 99999;

		return $base + $id;
	}


	/**
	 * Vrati ID skupiny
	 *
	 * @param $varSymbol
	 *
	 * @return int|null
	 */
	public static function getIdFromVarSymbol($varSymbol)
	{
		if (empty($varSymbol))
		{
			return null;
		}

		$varSymbol = str_replace(' ', '', $varSymbol);
		$varSymbol = (int) $varSymbol;

		// 35100001 - 35199999
		$base = 35100000;
		$max = 99999;
		// Kdyz bude ID vetsi jak 9999 tak jsme v haji =)

		if ($varSymbol <= $base || $varSymbol > $base + $max)
		{
			return null;
		}
		$id = $varSymbol - $base;

		return (int) $id;
	}


	/**
	 * Skupině se aktualizuje stav podle stavu všech členů
	 */
	public function updateStatus()
	{
		$participants = $this->getConfirmedParticipants();
		$confirmed = !empty($participants);

		$paid = $arrived = $left = $confirmed;
		foreach ($participants as $participant)
		{
			$paid &= $participant->isPaid();
			$arrived &= $participant->isArrived();
			$left &= $participant->isLeft();
		}

		$this->confirmed = $confirmed;
		$this->paid = $paid;
		$this->arrived = $arrived;
		$this->left = $left;
	}



}
