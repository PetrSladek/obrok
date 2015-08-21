<?php
/**
 * Tým servisáka - entita
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
 * @Entity(repositoryClass="App\Model\Repositories\TeamsRepository")
 * @Table(name="teams")
 */
class Team extends Doctrine\Entities\BaseEntity {

    use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column


    /**
     * Zkratka týmu
     * @Column(type="string", length=255)
     */
    protected $abbr;

    /**
     * Nazev týmu
     * @Column(type="string", length=255)
     */
    protected $name;


}