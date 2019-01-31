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
use Nette\Utils\DateTime;

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

    /**
     * Cena obroku pro 1 servisaka
     */
    public const PRICE = 400;
    


	/** @Column(type="string", length=512, nullable=true) */
	protected $role;

	/**
	 * Zkusenosti s podobnymi akcemi
	 * @Column(type="json_array", nullable=true)
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
	const EXPIRIENCES = [
		"realizace programu" => "realizace programu",
		"registrace lidí" => " registrace lidí",
		"obsluha jídelny" => "obsluha jídelny",
		"zajištění zázemí" => "zajištění zázemí",
		"čistota na akci" => "čistota na akci",
		"hlídání areálu" => "hlídání areálu",
	];

    /**
     * Dietologické požadavky
     * @Column(type="string", nullable=true)
     *
     * @var string|null
     */
    protected $diet;

	/**
	 * Dietologické požadavky
	 * @Column(type="json_array", nullable=true)
	 *
	 * @var array|null
	 */
	protected $dietSpecification = [];

	/**
	 * Dietologické požadavky
	 * @Column(type="text", nullable=true)
	 *
	 * @var string
	 */
	protected $dietNote;

	/**
	 * Diety na výběr
	 */
	const DIET = [
	    "vegetariánská" => "vegetariánská",
		"s masem" => "s masem",
		"vegan" => "vegan",
	];
	const DIET_SPECIFICATION = [
        "bez lepku" => "bez lepku",
        "bez mléka" => "bez mléka",
    ];

	/**
	 * Chce pomoct s pripravami?
	 * @Column(type="boolean")
	 */
	protected $helpPreparation = false;

	/**
	 * Prijede na stavecku?
     * @Column(type="text", nullable=true)
     *
     * @var string
	 */
	protected $hobbies;

    /**
     * Zůstane na bouračku na stavecku?
     * @Column(type="boolean")
     */
    protected $speakEnglish = false;


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
	 * Chce tištěný HandBook?
	 *
	 * @var bool
	 *
	 * @Column(type="boolean")
	 */
	protected $wantHandbook = false;

    /**
     * Byl odeslán email s platebními informacemi
     *
     * @var bool
     *
     * @Column(type="boolean")
     */
    protected $sentPaymentInfoEmail = false;

	/**
	 * Velikost tricka
	 * @Column(type="string")
	 */
	protected $tshirtSize = "man-L";

	/**
	 * Výběr velikosti trika
	 */
	const TSHIRT_SIZES = [
		"man-XS"    => 'Pánské XS',
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
		'2019-05-20' => '20.5. pondělí - nadšenec',
        '2019-05-21' => '21.5. úterý - stavěč',
		'2019-05-22' => '22.5. středa - servisák',
	];

	/**
	 * Výběr datumu odjezdu
	 */
	const DEPARTURE_DATES = [
		'2019-05-26' => '26.5. neděle',
//		'2019-05-27' => '27.5. pondělí',
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
     * @return null|string
     */
    public function getDiet(): ?string
    {
        return $this->diet;
    }

    /**
     * @param null|string $diet
     */
    public function setDiet(?string $diet): void
    {
        $this->diet = $diet;
    }


	/**
	 * @return array|null
	 */
	public function getDietSpecification()
	{
		return $this->dietSpecification;
	}

	/**
	 * @param array|null $dietSpecification
	 */
	public function setDietSpecification(array $dietSpecification)
	{
		$this->dietSpecification = $dietSpecification;
	}

	/**
	 * @return string
	 */
	public function getDietNote(): ?string
	{
		return $this->dietNote;
	}

	/**
	 * @param string $dietNote
	 */
	public function setDietNote(?string $dietNote)
	{
		$this->dietNote = $dietNote;
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
	public function getHobbies()
	{
		return $this->hobbies;
	}

	/**
	 * @param mixed $hobbies
	 */
	public function setHobbies($hobbies)
	{
		$this->hobbies = $hobbies;
	}

	/**
	 * @return bool
	 */
	public function isSpeakEnglish() : bool
	{
		return (bool) $this->speakEnglish;
	}

	/**
	 * @param bool $speakEnglish
	 */
	public function setSpeakEnglish(bool $speakEnglish = true) : void
	{
		$this->speakEnglish = $speakEnglish;
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
	 * @return boolean
	 */
	public function getWantHandbook(): bool
	{
		return (bool) $this->wantHandbook;
	}

	/**
	 * @param boolean $wantHandbook
	 */
	public function setWantHandbook(bool $wantHandbook)
	{
		$this->wantHandbook = $wantHandbook;
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

		// 35200001 - 35299999
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
     * Vrati cenu za Obrok
     * @return int
     */
	public function getPrice()
    {
        return self::PRICE;
    }


    /**
     * Datum do kdy musi zaplatit
     *
     * @return DateTime
     */
    public function getPayToDate()
    {
        $createDate = DateTime::from($this->getCreatedAt());
        $publicationDate = new DateTime('2019-02-01');

        $payToDate = $createDate > $publicationDate ? $createDate : $publicationDate;
        $payToDate->modify('+ 14 days midnight');

        return $payToDate;
    }

    /**
     * @return bool
     */
    public function isSentPaymentInfoEmail(): bool
    {
        return $this->sentPaymentInfoEmail;
    }

    /**
     * @param bool $sentPaymentInfoEmail
     */
    public function setSentPaymentInfoEmail(bool $sentPaymentInfoEmail): void
    {
        $this->sentPaymentInfoEmail = $sentPaymentInfoEmail;
    }



	/**
	 * Vrati objekt s nette identitou
	 */
	public function toIdentity()
	{
		return new \Nette\Security\Identity($this->getId(), array_merge([Person::TYPE_SERVICETEAM], explode(" ", $this->role)));
	}

}