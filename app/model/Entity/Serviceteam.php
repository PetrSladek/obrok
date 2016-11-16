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
	 * @Column(type="text", nullable=true)
	 */
	protected $experience;

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
	 * Velikost tricka
	 * @Column(type="string")
	 */
	protected $tshirtSize = "man-L";

	/**
	 * @var array
	 */
	public static $tShirtSizes = array(
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
		"woman-4XL" => 'Dámské 4XL');

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

		return self::$tShirtSizes[$this->tshirtSize];
	}


	/**
     * Vrátí variabilní symbol tohoto servisáka
     *
	 * @return int
	 */
	public function getVarSymbol()
	{
		return self::getVarSymbolFromId($this->id);
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