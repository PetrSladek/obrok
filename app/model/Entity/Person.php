<?php

namespace App\Model\Entity;

use App\Model\Address;
use App\Model\Phone;
use Kdyby\Doctrine\Entities\BaseEntity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\MappedSuperclass;
use Doctrine\ORM\Mapping\DiscriminatorMap;
use Doctrine\ORM\Mapping\DiscriminatorColumn;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\InheritanceType;
use Doctrine\ORM\Mapping\ManyToOne;
use Nette\Utils\DateTime;

/**
 * @author Petr /Peggy/ Sládek <petr.sladek@skaut.cz>
 *
 * @Entity(repositoryClass="App\Model\Repositories\PersonsRepository")
 * @InheritanceType("SINGLE_TABLE")
 * @DiscriminatorMap({
 *  Person::TYPE_PARTICIPANT = "Participant",
 *  Person::TYPE_SERVICETEAM = "Serviceteam",
 *  Person::TYPE_UNSPECIFIED = "UnspecifiedPerson"
 * })
 * @DiscriminatorColumn(name="type", columnDefinition="ENUM('unspecified', 'participant', 'serviceteam')"))
 */
abstract class Person
{
	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	use \Kdyby\Doctrine\Entities\MagicAccessors;

	const GENDER_MALE = 'male';

	const GENDER_FEMALE = 'female';

	const TYPE_UNSPECIFIED = 'unspecified'; // pred zaregistrovanim, ale uz se prihlasil pres skautis

	const TYPE_PARTICIPANT = 'participant'; // zaregistrovan jako ucastnik

	const TYPE_SERVICETEAM = 'serviceteam'; // zaregistrovan jako servisak

	/**
	 * Datum vytvoreni
	 * @var \DateTime
	 * @Column(type="datetime")
	 */
	protected $createdAt;


	/**
	 * Datum registrace (Do ST/Účastníků)
	 * @var \DateTime|null
	 * @Column(type="datetime", nullable=true)
	 */
	protected $registeredAt;

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
	 * Číslo jednotky
	 *
	 * @Column(type="string", length=255, nullable=true)
	 */
	protected $unitNumber;

	/**
	 * Skautis User ID
	 * @Column(type="integer", nullable=true)
	 */
	protected $skautisUserId = 0;

	/**
	 * Skautis Person ID
	 * @Column(type="integer", nullable=true)
	 */
	protected $skautisPersonId = 0;

	/**
	 * Slouzi ke kratkodobmeu ulozeni hash pro prihlaseni z adminu pred redirectem
	 * @Column(type="string", length=255, nullable=true)
	 */
	protected $quickLoginHash;


	/**
	 * Person constructor.
	 */
	public function __construct()
	{
		$this->createdAt = new \DateTime("now");
	}

	/**
	 * @return \DateTime
	 */
	public function getCreatedAt()
	{
		return $this->createdAt;
	}

	/**
	 * @param \DateTime $createdAt
	 */
	public function setCreatedAt($createdAt)
	{
		$this->createdAt = $createdAt;
	}

	/**
	 * @return \DateTime|null
	 */
	public function getRegisteredAt()
	{
		return $this->registeredAt;
	}

	/**
	 * @param \DateTime|null $registeredAt
	 */
	public function setRegisteredAt($registeredAt)
	{
		$this->registeredAt = $registeredAt;
	}


	/**
	 * @return string
	 */
	public function getRole()
	{
		return $this->role;
	}

	/**
	 * @param string $role
	 */
	public function setRole($role)
	{
		$this->role = $role;
	}

	/**
	 * @return \DateTime
	 */
	public function getLastLogin()
	{
		return $this->lastLogin;
	}

	/**
	 * @param \DateTime $lastLogin
	 */
	public function setLastLogin($lastLogin)
	{
		$this->lastLogin = $lastLogin;
	}

	/**
	 * @return string
	 */
	public function getGender()
	{
		return $this->gender;
	}

	/**
	 * @param string $gender
	 */
	public function setGender($gender)
	{
		$this->gender = $gender;
	}

	/**
	 * @return string
	 */
	public function getAddressStreet()
	{
		return $this->addressStreet;
	}

	/**
	 * @param string $addressStreet
	 */
	public function setAddressStreet($addressStreet)
	{
		$this->addressStreet = $addressStreet;
	}

	/**
	 * @return string
	 */
	public function getAddressCity()
	{
		return $this->addressCity;
	}

	/**
	 * @param string $addressCity
	 */
	public function setAddressCity($addressCity)
	{
		$this->addressCity = $addressCity;
	}

	/**
	 * @return string
	 */
	public function getAddressPostcode()
	{
		return $this->addressPostcode;
	}

	/**
	 * @param string $addressPostcode
	 */
	public function setAddressPostcode($addressPostcode)
	{
		$this->addressPostcode = $addressPostcode;
	}

	/**
	 * @return string
	 */
	public function getEmail()
	{
		return $this->email;
	}

	/**
	 * @param string $email
	 */
	public function setEmail($email)
	{
		$this->email = $email;
	}

	/**
	 * @return \DateTime
	 */
	public function getBirthdate()
	{
		return $this->birthdate;
	}

	/**
	 * @param \DateTime $birthdate
	 */
	public function setBirthdate($birthdate)
	{
		$this->birthdate = $birthdate;
	}

	/**
	 * @return string
	 */
	public function getHealth()
	{
		return $this->health;
	}

	/**
	 * @param string $health
	 */
	public function setHealth($health)
	{
		$this->health = $health;
	}

	/**
	 * @return string
	 */
	public function getNote()
	{
		return $this->note;
	}

	/**
	 * @param string $note
	 */
	public function setNote($note)
	{
		$this->note = $note;
	}

	/**
	 * @return string
	 */
	public function getNoteInternal()
	{
		return $this->noteInternal;
	}

	/**
	 * @param string $noteInternal
	 */
	public function setNoteInternal($noteInternal)
	{
		$this->noteInternal = $noteInternal;
	}

	/**
	 * @return string
	 */
	public function getSkautisUserId()
	{
		return $this->skautisUserId;
	}

	/**
	 * @param string $skautisUserId
	 */
	public function setSkautisUserId($skautisUserId)
	{
		$this->skautisUserId = $skautisUserId;
	}

	/**
	 * @return string
	 */
	public function getSkautisPersonId()
	{
		return $this->skautisPersonId;
	}

	/**
	 * @param string $skautisPersonId
	 */
	public function setSkautisPersonId($skautisPersonId)
	{
		$this->skautisPersonId = $skautisPersonId;
	}

	/**
	 * @return mixed
	 */
	public function getPaid()
	{
		return $this->paid;
	}

	/**
	 * @param mixed $paid
	 */
	public function setPaid($paid)
	{
		$this->paid = $paid;
	}

	/**
	 * @return mixed
	 */
	public function getQuickLoginHash()
	{
		return $this->quickLoginHash;
	}

	/**
	 * @param mixed $quickLoginHash
	 */
	public function setQuickLoginHash($quickLoginHash)
	{
		$this->quickLoginHash = $quickLoginHash;
	}



	/**
	 * Vrati cele jmeno, pripadne cele jmeno s prezdivkou
	 * @return string
	 */
	public function getFullname()
	{
		if (!empty($this->nickName))
		{
			return sprintf('%s %s (%s)', $this->firstName, $this->lastName, $this->nickName);
		}

		return sprintf('%s %s', $this->firstName, $this->lastName);
	}


	/**
	 * @param      $firstName
	 * @param      $lastName
	 * @param null $nickName
	 */
	public function setFullName($firstName, $lastName, $nickName = null)
	{
		$this->firstName = $firstName;
		$this->lastName = $lastName;
		if ($nickName)
		{
			$this->nickName = $nickName;
		}

	}


	/**
	 * @param DateTime|string $inDate
	 *
	 * @return int|null
	 */
	public function getAge($inDate = null)
	{
		if (empty($this->birthdate))
		{
			return null;
		}

		$now = DateTime::from($inDate);
		/** @see http://php.vrana.cz/zjisteni-veku-z-data-narozeni.php */
		$age = (int) floor(($now->format("Ymd") - $this->birthdate->format("Ymd")) / 10000);

		return $age;

	}


	/**
	 * @param $phone
	 */
	public function setPhone($phone)
	{
		// odstrani mezery
		$this->phone = trim(str_replace(" ", "", (string) $phone));
	}


	/**
	 * @param bool $html
	 *
	 * @return Phone|null
	 */
	public function getPhone()
	{
		if (empty($this->phone))
		{
			return null;
		}

		return new Phone($this->phone);
	}


	/**
	 * @return Address
	 */
	public function getAddress()
	{
		return new Address($this->addressStreet, $this->addressCity, $this->addressPostcode);
	}


	/**
	 * @param Address $address
	 */
	public function setAddress(Address $address)
	{
		$this->addressStreet = $address->street;
		$this->addressCity = $address->city;
		$this->addressPostcode = $address->postalCode;
	}


	/**
	 * @return bool
	 */
	public function isMale()
	{
		return $this->gender == self::GENDER_MALE;
	}


	/**
	 * @return bool
	 */
	public function isFemale()
	{
		return $this->gender == self::GENDER_MALE;
	}


	/**
	 * @return bool
	 */
	public function isConfirmed()
	{
		return (bool) $this->confirmed;
	}


	/**
	 * @return bool
	 */
	public function isPaid()
	{
		return (bool) $this->paid;
	}

	/**
	 * @return bool
	 */
	public function isArrived()
	{
		return (bool) $this->arrived;
	}


	/**
	 * @return bool
	 */
	public function isLeft()
	{
		return (bool) $this->left;
	}

	/**
	 * Vrati objekt s nette identitou
	 */
	public function toIdentity()
	{
		return new \Nette\Security\Identity($this->getId(), self::TYPE_UNSPECIFIED);
	}

	/**
	 * @return string
	 */
	public function getUnitNumber()
	{
		return $this->unitNumber;
	}

	/**
	 * @param mixed $unitNumber
	 */
	public function setUnitNumber($unitNumber)
	{
		$this->unitNumber = (string) $unitNumber;
	}

	/**
	 * @return string
	 */
	public function getFirstName()
	{
		return $this->firstName;
	}

	/**
	 * @param string $firstName
	 */
	public function setFirstName($firstName)
	{
		$this->firstName = $firstName;
	}

	/**
	 * @return string
	 */
	public function getLastName()
	{
		return $this->lastName;
	}

	/**
	 * @param string $lastName
	 */
	public function setLastName($lastName)
	{
		$this->lastName = $lastName;
	}

	/**
	 * @return string
	 */
	public function getNickName()
	{
		return $this->nickName;
	}

	/**
	 * @param string $nickName
	 */
	public function setNickName($nickName)
	{
		$this->nickName = $nickName;
	}


    /**
     * Datum do kdy musi zaplatit
     *
     * @return DateTime
     */
    public function getPayToDate()
    {
        $registeredAt = DateTime::from($this->getRegisteredAt());
        $publicationDate = new DateTime('2019-04-03');

        $payToDate = $registeredAt > $publicationDate ? $registeredAt : $publicationDate;
        $payToDate->modify('+ 14 days midnight');

        return $payToDate;
    }


}