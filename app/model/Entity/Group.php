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
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;
use Nette\NotImplementedException;

/**
 * @Entity(repositoryClass="App\Model\Repositories\GroupsRepository")
 * @Table(name="groups")
 *
 * @property \DateTime        $createdAt
 * @property string           $name
 * @property string           $city
 * @property string           $region
 * @property float            $locationLat
 * @property float            $locationLng
 * @property string           $note
 * @property string           $noteInternal
 * @property string           $avatarFilename
 * @property string           $avatarCrop
 * @property bool             $confirmed
 * @property Participant|null $boss
 * @property Participant[]    $participants
 */
class Group extends Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

	/**
	 * Datum vytvoreni
	 * @Column(type="datetime")
	 * @var \DateTime
	 */
	protected $createdAt;

//    /**
//     * Variabilni symbol
//     * @Column(type="integer", length=255)
//     */
//    protected $varSymbol;

	/**
	 * Nazev ucastnicke skupiny
	 * @Column(type="string", length=255)
	 */
	protected $name;

	/**
	 * Mesto
	 * @Column(type="string", length=255)
	 */
	protected $city;

	/**
	 * Kraj
	 * @Column(type="string", length=255, nullable=true)
	 */
	protected $region;

	/**
	 * Geolokace LAT
	 * @Column(type="float", nullable=true)
	 */
	protected $locationLat;

	/**
	 * Geolokace LNG
	 * @Column(type="float", nullable=true)
	 */
	protected $locationLng;

	/**
	 * Poznamka pri registraci
	 * @Column(type="text", nullable=true)
	 */
	protected $note;

	/**
	 * Interni poznamka
	 * @Column(type="text", nullable=true)
	 */
	protected $noteInternal;

	/**
	 * Avatar jmeno souboru
	 * @Column(type="string", length=1024, nullable=true)
	 */
	protected $avatarFilename;

	/**
	 * Avatar oriznutí
	 * @Column(type="json_array", nullable=true)
	 */
	protected $avatarCrop;

	// Asociace

	/**
	 * Ucastnici ve skupine
	 * @OneToMany(targetEntity="Participant", mappedBy="group", cascade={"persist"})
	 * @var Participant[]|ArrayCollection
	 **/
	private $participants;

	/**
	 * Vedouci skupiny (18+)
	 * @ManyToOne(targetEntity="Participant", cascade={"persist"})
	 * @JoinColumn(name="boss_id", referencedColumnName="id")
	 * @var Participant
	 **/
	private $boss;


	public function __construct()
	{
		$this->createdAt = new \DateTime('now');
		$this->participants = new ArrayCollection();
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
		foreach ($this->getConfirmedParticipants() as $participant)
		{
			if ($participant->getAge() >= 18)
			{
				$this->setBoss($participant);
				break;
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
	}


	/**
	 * Smaze ucastnika z teto skupiny
	 *
	 * @param Participant $participant
	 */
	public function removeParticipant(Participant $participant)
	{
		if ($participant->getGroup() != $this)
		{
			throw new \InvalidArgumentException('Ucastnik neni v teto skupine');
		}

		$this->participants->remove($participant);
		$participant->setGroup(null);
	}


	/**
	 * Je skupina potvrzena ze prijede? (ma alespon jednoho platneho ucastnika)
	 * @return bool
	 */
	public function isConfirmed()
	{
		return $this->getConfirmedParticipantsCount() > 0;
	}


	/**
	 * Je zaplaceno za vsechny platne ucastniky?
	 * @return bool
	 */
	public function isPaid()
	{
		return $this->isConfirmed() && $this->getConfirmedParticipantsCount() == $this->getConfirmedParticipantsCountByStatus('paid');
	}


	/**
	 * Odjeli vsichni platni ucastnici z teto skupiny?
	 * @return bool
	 */
	public function isArrived()
	{
		return $this->isConfirmed() && $this->getConfirmedParticipantsCount() == $this->getConfirmedParticipantsCountByStatus('arrived');
	}


	/**
	 * Odjeli vsichni platni ucastnici z teto skupiny?
	 * @return bool
	 */
	public function isLeft()
	{
		return $this->isConfirmed() && $this->getConfirmedParticipantsCount() == $this->getConfirmedParticipantsCountByStatus('left');
	}

//    /**
//     * Je preplaceno?
//     * Zaplaceno i za ucastniky kteri se nezucastni
//     * @return bool
//     */
//    public function isOverPaid() {
//        return  $this->getParticipantsCountByStatus('paid') > $this->getConfirmedParticipantsCountByStatus('paid');
//    }

	/**
	 * Je zaplaceno alespon castecne?
	 * Zaplaceno za cast skupiny ale ne za vsechny
	 * @return bool
	 */
	public function isPartlyPaid()
	{
		return $this->getConfirmedParticipantsCountByStatus('paid') > 0 && !$this->isPaid();
	}

//    /**
//     * Neni zaplaceno za nikoho
//     * Nebylo jeste placeno vubec za nikoho
//     * @return bool
//     */
//    public function isNotPaid() {
//        return $this->getParticipantsCountByStatus('paid') == 0;
//    }

//    /**
//     * Kolik je preplacenych mist
//     * @return int
//     * @deprecated
//     */
//    public function getOverPaidPlaces() {
//        return 0;
////        return max(0, $this->paidFor - $this->getConfirmedParticipantsCountByStatus('paid'));
//    }

	/**
	 * Vrati aktivini ucastniky (se kteryma se pocita ze prijedou)
	 * @return \Doctrine\Common\Collections\Collection|Participant[]
	 */
	public function getConfirmedParticipants()
	{
		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('confirmed', true));

		return $this->participants->matching($criteria);
	}


	/**
	 * Vrati neaktivini ucastniky (kteri neprijedou)
	 * @return \Doctrine\Common\Collections\Collection|Participant[]
	 */
	public function getUnconfirmedParticipants()
	{
		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('confirmed', false));

		return $this->participants->matching($criteria);
	}


	/**
	 * Vrati pocet aktivinich ucastniku (se kteryma se pocita ze prijedou)
	 * @return int
	 */
	public function getConfirmedParticipantsCount()
	{
		return $this->getConfirmedParticipants()->count();
	}


	protected function getParticipantsByStatus($status, $value = true)
	{
		if (!in_array($status, ['confirmed', 'paid', 'arrived', 'left']))
		{
			throw new \InvalidArgumentException("Wrong status name");
		}

		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq($status, $value));

		return $this->participants->matching($criteria);
	}


	protected function getParticipantsCouuntByStatus($status, $value = true)
	{
		return $this->getParticipantsByStatus($status, $value)->count();
	}


	/**
	 * Vrati vsechny platne ucastniky, kteri jsou ve stavu $status
	 *
	 * @param      $status
	 * @param bool $value
	 *
	 * @return mixed
	 */
	protected function getConfirmedParticipantsByStatus($status, $value = true)
	{
		if (!in_array($status, ['paid', 'arrived', 'left']))
		{
			throw new \InvalidArgumentException("Wrong status name");
		}

		$criteria = Criteria::create();
		$criteria->where(Criteria::expr()->eq('confirmed', true))
				 ->andWhere(Criteria::expr()->eq($status, $value));

		return $this->participants->matching($criteria);
	}


	/**
	 * Vrati pocet vsech platnych ucastniku, kteri jsou ve stavu $status
	 *
	 * @param      $status
	 * @param bool $value
	 *
	 * @return mixed
	 */
	protected function getConfirmedParticipantsCountByStatus($status, $value = true)
	{
		return $this->getConfirmedParticipantsByStatus($status, $value)->count();
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
	 * @return string Vytvori hash pro pozvani ucastnika do skupiny
	 */
	public function getInvitationHash($key)
	{
		$hash = sha1("{$this->createdAt->getTimestamp()}|$key|{$this->id}");

		return substr($hash, 0, 8); // nemusi by tak dlouhy
	}


	public function getVarSymbol()
	{
		return self::getVarSymbolFromId($this->id);
	}


	/**
	 * Vrati variabilni symbol skupiny
	 *
	 * @param $id ID Skupiny
	 *
	 * @return int|null
	 */
	public static function getVarSymbolFromId($id)
	{
		if (empty($id))
		{
			return null;
		}

		// 1510001 - 1519999
		$base = 1510000;
		$max = 9999;

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

		// 1510001 - 1519999
		$base = 1510000;
		$max = 9999;
		// Kdyz bude ID vetsi jak 9999 tak jsme v haji =)

		if ($varSymbol <= $base || $varSymbol > $base + $max)
		{
			return null;
		}
		$id = $varSymbol - $base;

		return (int) $id;
	}

}