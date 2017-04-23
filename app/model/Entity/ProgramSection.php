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
 * @Entity(repositoryClass="App\Model\Repositories\ProgramsSectionsRepository")
 *
 * @property string    $title
 * @property string    $subTitle
 */
class ProgramSection
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column
	use \Kdyby\Doctrine\Entities\MagicAccessors;

	/**
	 * TODO dat to pryč všude kde se to používá
	 */
	const KRINSPIRO = 9;

	/**
	 * Nazev programove kategorie(bloku) - Vapro, Inspiro, Zivly, Sluzba, ...
	 * @Column(type="string", length=255)
	 */
	protected $title;

	/**
	 * Pod titul programove kategorie(bloku) - 1.blok, 2.blok, Ohen ,...
	 * @Column(type="string", length=255)
	 */
	protected $subTitle;

	/**
	 * Programy v teto kategorii
	 * @OneToMany(targetEntity="Program", mappedBy="section", cascade={"persist"})
	 * @var Participant[]|ArrayCollection
	 **/
	private $programs;


	/**
	 * ProgramSection constructor.
	 */
	public function __construct()
	{
		$this->programs = new ArrayCollection();
	}

	/**
	 * @return Program[]
	 */
	public function getPrograms()
	{
		return $this->programs->toArray();
	}

}