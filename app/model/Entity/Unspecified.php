<?php
/**
 * Servisak - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use App\Model\Address;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\ManyToMany;
use Doctrine\ORM\Mapping\OrderBy;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;
use Nette\InvalidStateException;
use Nette\Utils\DateTime;

/**
 * @Entity(repositoryClass="App\Model\Repositories\UnspecifiedsRepository")
 */
class Unspecified extends Person {

    /**
     * Vrati objekt s nette identitou
     */
    public function toIdentity() {
        return new \Nette\Security\Identity($this->id, Person::TYPE_UNSPECIFIED);
    }



}