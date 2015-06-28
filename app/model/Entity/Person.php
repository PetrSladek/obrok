<?php


namespace App\Model\Entity;

use App\Model\Address;
use App\Model\Phone;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Nette\Utils\DateTime;


/**
 * @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 *
 * @MappedSuperclass
 *
 * @property \DateTime $createdAt
 * @property string $role
 * @property \DateTime $lastLogin
 * @property string $gender
 * @property string $firstName
 * @property string $lastName
 * @property string $nickName
 * @property string $addressStreet
 * @property string $addressCity
 * @property string $addressPostcode
 * @property bool $confirmed
 * @property bool $arrived
 * @property bool $left
 * @property string $email
 * @property string $phone
 * @property \DateTime $birthdate
 * @property string $health
 * @property string $note
 * @property string $noteInternal
 * @property string $skautisUserId
 * @property string $skautisPersonId
 */
abstract class Person extends BaseEntity {

    use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

    const GENDER_MALE = 'male';
    const GENDER_FEMALE = 'female';

    const ROLE_GUEST = 'guest'; // pred zaregistrovanim
    const ROLE_PARTICIPANT = 'participant'; // zaregistrovan jako ucastnik
    const ROLE_SERVICETEAM = 'serviceteam'; // zaregistrovan jako servisak

    /**
     * Datum vytvoreni
     * @var \DateTime
     * @Column(type="datetime")
     */
    protected $createdAt;


//    /**
//     * Hash hesla
//     * @Column(type="string", length=255, nullable=true)
//     */
//    protected $password;


    /** @Column(type="datetime", nullable=true) */
    protected $lastLogin;



    /**
     * Pohlavi
     * @Column(type="string", columnDefinition="ENUM('male', 'female')")
     */
    protected $gender = self::GENDER_MALE;


    /** @Column(type="string", length=255) */
    protected $firstName;

    /** @Column(type="string", length=255) */
    protected $lastName;

    /** @Column(type="string", length=255, nullable=true) */
    protected $nickName;



    /**
     * Adresa - Ulice a cp
     * @Column(type="string", length=255, nullable=true)
     */
    protected $addressStreet;

    /**
     * Adresa - Mesto
     * @Column(type="string", length=255, nullable=true)
     */
    protected $addressCity;

    /**
     * Adresa - PSC
     * @Column(type="string", length=255, nullable=true)
     */
    protected $addressPostcode;






    /**
     * Kontaktni email
     * @Column(type="string", length=255, nullable=true)
     */
    protected $email;

    /**
     * Kontaktni telefon
     * @Column(type="string", length=255, nullable=true)
     */
    protected $phone;

    /**
     * Datum narozeni
     * @Column(type="date", nullable=true)
     */
    protected $birthdate;


    /**
     * Zdravotni omezeni
     * @Column(type="text", nullable=true)
     */
    protected $health;

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
     * Pocita se s nim i kdyz treba jeste nezaplatil
     * @Column(type="boolean")
     */
    protected $confirmed = true;

    /**
     * Zaplacený - zaplatil poplatek
     * @Column(type="boolean")
     */
    protected $paid = false;

    /**
     * Přijeli - prijel na akci a fyzicky se zaregistrovali (ohlasili)
     * @Column(type="boolean")
     */
    protected $arrived = false;

    /**
     * Odjeli - odjel z akce (deregistrovali se)
     * @Column(name="`left`", type="boolean")
     */
    protected $left = false;



    /**
     * Skautis User ID
     * @Column(type="integer", nullable=true)
     */
    protected $skautisUserId;

    /**
     * Skautis Person ID
     * @Column(type="integer", nullable=true)
     */
    protected $skautisPersonId;

    /**
     * Slouzi ke kratkodobmeu ulozeni hash pro prihlaseni z adminu pred redirectem
     * @Column(type="string", length=255, nullable=true)
     */
    protected $quickLoginHash;


    public function __construct()
    {
        $this->createdAt = new \DateTime("now");
    }





    /**
     * Vrati cele jmeno, pripadne cele jmeno s prezdivkou
     * @return string
     */
    public function getFullname() {
        if(!empty($this->nickName))
            return sprintf('%s %s (%s)', $this->firstName, $this->lastName, $this->nickName);
        return sprintf('%s %s', $this->firstName, $this->lastName);
    }

    public function setFullName($firstName, $lastName, $nickName = null) {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        if($nickName)
            $this->nickName = $nickName;

    }

    /**
     * @param DateTime|string $inDate
     * @return int|null
     */
    public function getAge($inDate=null) {
        if(empty($this->birthdate))
            return null;

        $now = DateTime::from($inDate);
        /** @see http://php.vrana.cz/zjisteni-veku-z-data-narozeni.php */
        $age = (int) floor(($now->format("Ymd") - $this->birthdate->format("Ymd")) / 10000);

        return $age;

    }


    public function setPhone($phone) {
        // odstrani mezery
        $this->phone = trim(str_replace(" ","", (string) $phone));
    }
    /**
     * @param bool $html
     * @return Phone|null
     */
    public function getPhone() {
        if(empty($this->phone))
            return null;

        return new Phone($this->phone);
    }



    public function getAddress() {
        return new Address($this->addressStreet, $this->addressCity, $this->addressPostcode);
    }





} 