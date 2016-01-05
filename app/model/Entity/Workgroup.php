<?php
/**
 * Pracovni skupina servisáka - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;

use Kdyby\Doctrine;

/**
 * @Entity(repositoryClass="App\Model\Repositories\WorkgroupsRepository")
 * @Table(name="workgroups")
 * @property string $name
 */
class Workgroup extends Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

	/**
	 * Nazev pracovni skupiny
	 * @Column(type="string", length=255)
	 */
	protected $name;

}