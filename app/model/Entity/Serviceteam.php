<?php
/**
 * Servisak - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;

use Kdyby\Doctrine;

/**
 * @Entity(repositoryClass="App\Model\Repositories\ServiceteamRepository")
 *
 * @property Team|null      $team
 * @property Job|null       $job
 * @property Workgroup|null $workgroup
 */
class Serviceteam extends Person
{
	use Avatarable;

	/** @Column(type="string", length=512, nullable=true) */
	protected $role;

	/**
	 * Zkusenosti s podobnymi akcemi
	 * @Column(type="json", nullable=true)
	 *
	 * @var array|null
	 */
	protected $experience = [];

	/**
	 * Zkusenosti s podobnymi akcemi
	 * @Column(type="text", nullable=true)
	 *
	 * @var string
	 */
	protected $experienceNote;


	/**
	 * Zkušenosti/Dovednosti na výběr
	 */
	const EXPIRIENCE = [
		"Program",
		"Bezpečnost",
		"Sanita",
		"Zázemí",
		"Jídlo",
		"Logistika",
		"Registrace"
	];

	/**
	 * Chce pomoct s pripravami?
	 * @Column(type="boolean")
	 */
	protected $helpPreparation = false;

	/**
	 * Prijede na stavecku?
	 * @Column(type="boolean")
	 */
	protected $arrivesToBuilding = false;

    /**
     * Zůstane na bouračku na stavecku?
     * @Column(type="boolean")
     */
    protected $stayToDestroy = false;


	/**
	 * Datum plánovaného příjezdu
	 * @var \DateTimeInterface
	 * @Column(type="date", nullable=true)
	 */
	protected $arriveDate = null;

	/**
	 * Datum plánovaného odjezdu
	 * @var \DateTimeInterface
	 * @Column(type="date", nullable=true)
	 */
	protected $departureDate = null;

	/**
	 * Velikost tricka
	 * @Column(type="string")
	 */
	protected $tshirtSize = "man-L";

	/**
	 * Výběr velikosti trika
	 */
	const TSHIRT_SIZES = [
		"man-S"     => 'Pánské S',
		"man-M"     => 'Pánské M',
		"man-L"     => 'Pánské L',
		"man-XL"    => 'Pánské XL',
		"man-XXL"   => 'Pánské XXL',
		"man-3XL"   => 'Pánské 3XL',
		"man-4XL"   => 'Pánské 4XL',
		"woman-XS"  => 'Dámské XS',
		"woman-S"   => 'Dámské S',
		"woman-M"   => 'Dámské M',
		"woman-L"   => 'Dámské L',
		"woman-XL"  => 'Dámské XL',
		"woman-XXL" => 'Dámské XXL',
		"woman-3XL" => 'Dámské 3XL',
		"woman-4XL" => 'Dámské 4XL'
	];

	/**
	 * Výběr datumu příjezdu
	 */
	const ARRIVE_DATES = [
		'2019-06-19' => '19.6 neděle - Nadšenec',
		'2019-06-20' => '20.6 pondělí - Stavěč',
		'2019-06-21' => '21.6 úterý - Servisák',
	];

	/**
	 * Výběr datumu odjezdu
	 */
	const DEPARTURE_DATES = [
		'2019-06-26' => '26.6 neděle',
		'2019-06-27' => '27.6 pondělí',
	];

	/**
	 * Tym do ktereho spada
	 * @ManyToOne(targetEntity="Team")
	 * @JoinColumn(name="team_id", referencedColumnName="id")
	 * @var Team|null Tým pod který spadá
	 **/
	protected $team;

	/**
	 * Pracovni pozice ve ktere pracuje
	 * @ManyToOne(targetEntity="Workgroup")
	 * @JoinColumn(name="workgroup_id", referencedColumnName="id")
	 * @var Workgroup|null Pracovní skupina ve které je (podtým)
	 **/
	protected $workgroup;

	/**
	 * Pracovni pozice kterou zastava
	 * @ManyToOne(targetEntity="Job", cascade={"persist"})
	 * @JoinColumn(name="job_id", referencedColumnName="id")
	 * @var Job|null Pozice kerou v rámci (pod)týmu vykonává
	 **/
	protected $job;

	/**
	 * @return mixed
	 */
	public function getRole()
	{
		return $this->role;
	}

	/**
	 * @param mixed $role
	 */
	public function setRole($role)
	{
		$this->role = $role;
	}

	/**
	 * @return array|null
	 */
	public function getExperience()
	{
		return $this->experience;
	}

	/**
	 * @param mixed $experience
	 */
	public function setExperience(array $experience)
	{
		$this->experience = $experience;
	}

	/**
	 * @return string
	 */
	public function getExperienceNote()
	{
		return $this->experienceNote;
	}

	/**
	 * @param string $experienceNote
	 */
	public function setExperienceNote($experienceNote)
	{
		$this->experienceNote = $experienceNote;
	}



	/**
	 * @return mixed
	 */
	public function getHelpPreparation()
	{
		return $this->helpPreparation;
	}

	/**
	 * @param mixed $helpPreparation
	 */
	public function setHelpPreparation($helpPreparation)
	{
		$this->helpPreparation = $helpPreparation;
	}

	/**
	 * @return mixed
	 */
	public function getArrivesToBuilding()
	{
		return $this->arrivesToBuilding;
	}

	/**
	 * @param mixed $arrivesToBuilding
	 */
	public function setArrivesToBuilding($arrivesToBuilding)
	{
		$this->arrivesToBuilding = $arrivesToBuilding;
	}

	/**
	 * @return mixed
	 */
	public function getStayToDestroy()
	{
		return $this->stayToDestroy;
	}

	/**
	 * @param mixed $stayToDestroy
	 */
	public function setStayToDestroy($stayToDestroy)
	{
		$this->stayToDestroy = $stayToDestroy;
	}

	/**
	 * @return \DateTimeInterface
	 */
	public function getArriveDate()
	{
		return $this->arriveDate;
	}

	/**
	 * @param \DateTimeInterface $arriveDate
	 */
	public function setArriveDate(\DateTimeInterface $arriveDate)
	{
		$this->arriveDate = $arriveDate;
	}

	/**
	 * Vrátí popisek s datumem
	 *
	 * @return string
	 */
	public function getArriveDateTitle()
	{
		if (!$this->arriveDate)
		{
			return '-';

		}
		return self::ARRIVE_DATES[$this->arriveDate->format('Y-m-d')] ?? '';
	}

	/**
	 * @return \DateTimeInterface
	 */
	public function getDepartureDate()
	{
		return $this->departureDate;
	}

	/**
	 * @param \DateTimeInterface $departureDate
	 */
	public function setDepartureDate(\DateTimeInterface $departureDate)
	{
		$this->departureDate = $departureDate;
	}

	/**
	 * Vrátí popisek s datumem
	 *
	 * @return string
	 */
	public function getDepartureDateTitle()
	{
		if (!$this->departureDate)
		{
			return '-';

		}
		return self::DEPARTURE_DATES[$this->departureDate->format('Y-m-d')] ?? '';
	}


	/**
	 * @return mixed
	 */
	public function getTshirtSize()
	{
		return $this->tshirtSize;
	}

	/**
	 * @param mixed $tshirtSize
	 */
	public function setTshirtSize($tshirtSize)
	{
		$this->tshirtSize = $tshirtSize;
	}



	/**
	 * @return Team|null
	 */
	public function getTeam()
	{
		return $this->team;
	}


	/**
	 * @param Team|null $team
	 */
	public function setTeam($team)
	{
		$this->team = $team;
	}


	/**
	 * @return Workgroup|null
	 */
	public function getWorkgroup()
	{
		return $this->workgroup;
	}


	/**
	 * @param Workgroup|null $workgroup
	 */
	public function setWorkgroup($workgroup)
	{
		$this->workgroup = $workgroup;
	}


	/**
	 * @return Job|null
	 */
	public function getJob()
	{
		return $this->job;
	}


	/**
	 * @param Job|null $job
	 */
	public function setJob($job)
	{
		$this->job = $job;
	}




	/**
	 * @return string
	 */
	public function getTshirtSizeName()
	{
		if (empty($this->tshirtSize))
		{
			return '-';
		}

		return self::TSHIRT_SIZES[$this->tshirtSize];
	}


	/**
     * Vrátí variabilní symbol tohoto servisáka
     *
	 * @return int
	 */
	public function getVarSymbol()
	{
		return self::getVarSymbolFromId($this->getId());
	}


	/**
	 * Vrati var.Symbol Servisáka
	 *
	 * @param int $id
	 *
	 * @return int|null
	 */
	public static function getVarSymbolFromId($id)
	{
		if (empty($id))
		{
			return null;
		}

		// 15200001 - 15299999
		$base = 35200000;
		$max = 99999;

		// Kdyz bude ID vetsi jak 9999 tak jsme v haji =)

		return $base + $id;
	}


	/**
	 * Vrati ID serviska
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

		$base = 35200000;
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
	 * Vrati objekt s nette identitou
	 */
	public function toIdentity()
	{
		return new \Nette\Security\Identity($this->id, array_merge([Person::TYPE_SERVICETEAM], explode(" ", $this->role)));
	}

}