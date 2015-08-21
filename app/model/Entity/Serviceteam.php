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
 * @Entity(repositoryClass="App\Repositories\ServiceteamRepository")
 *
 * @property Team|null $team
 * @property Job|null $job
 * @property Workgroup|null $workgroup
 */
class Serviceteam extends Person {


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
     * Avatar jmeno souboru
     * @Column(type="string", length=1024, nullable=true)
     */
    protected $avatarFilename;

    /**
     * Avatar oriznutí
     * @Column(type="json_array", nullable=true)
     */
    protected $avatarCrop;


    /**
     * Velikost tricka
     * @Column(type="string")
     */
    protected $tshirtSize = "man-L";

    public static  $tShirtSizes = array(
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
        "woman-4XL"  => 'Dámské 4XL');


    // Asociace

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



    public function getTshirtSizeName() {
        if(empty($this->tshirtSize))
            return '-';
        return self::$tShirtSizes[ $this->tshirtSize ];
    }
    public function getArriveText() {
        if(!isset($this->arrivesToBuilding))
            return null;
        $arrive = array(false=>'Příjezd až na Obrok', true=>'Příjezd i na stavěcí týden od 7.6.2015');
        return $arrive[$this->arrivesToBuilding];
    }

    public function getVarSymbol() {
        return self::getVarSymbolFromId($this->id);
    }

    /**
     * Vrati var.Symbol Servisáka
     * @param $id
     * @return int|null
     */
    public static function getVarSymbolFromId($id) {
        if(empty($id))
            return null;

        // 1520001 - 1529999
        $base = 1520000;
        $max = 9999;
        // Kdyz bude ID vetsi jak 9999 tak jsme v haji =)

        return $base + $id;
    }

    /**
     * Vrati ID serviska
     * @param $varSymbol
     * @return int|null
     */
    public static function getIdFromVarSymbol($varSymbol) {
        if(empty($varSymbol))
            return null;

        $varSymbol = str_replace(' ','', $varSymbol);
        $varSymbol = (int) $varSymbol;

        $base = 1520000;
        $max = 9999;
        // Kdyz bude ID vetsi jak 9999 tak jsme v haji =)

        if($varSymbol <= $base || $varSymbol > $base+$max)
            return null;

        $id = $varSymbol - $base;
        return (int) $id;
    }


    /**
     * Vrati objekt s nette identitou
     */
    public function toIdentity() {
        return new \Nette\Security\Identity($this->id, array_merge([Person::TYPE_SERVICETEAM], explode(" ", $this->role)));
    }






}