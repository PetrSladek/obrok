<?php
/**
 * Pracovni pozice servisáka - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;

/**
 * @Entity(repositoryClass="App\Model\Repositories\JobsRepository")
 * @Table(name="jobs")
 * @property string $name
 */
class Job extends Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

	/**
	 * Nazev týmu
	 * @Column(type="string", length=255)
	 */
	protected $name;

}