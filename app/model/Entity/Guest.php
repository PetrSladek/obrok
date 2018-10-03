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
 * Host na obroku (Televize, Starostové,..)
 * @Entity
 */
class Guest
{
	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	use \Kdyby\Doctrine\Entities\MagicAccessors;
	use Avatarable;

	const GENDER_MALE = 'male';

	const GENDER_FEMALE = 'female';

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
	 * Přišel
	 * @Column(type="boolean")
	 */
	protected $arrived = false;

	/**
	 * Odešel
	 * @Column(name="`left`", type="boolean")
	 */
	protected $left = false;


}