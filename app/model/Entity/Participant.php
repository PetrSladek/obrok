<?php
/**
 * Servisak - entita
 *
 * @author Petr /Peggy/ Sladek
 */

namespace App\Model\Entity;

use App\Model\Address;
use Doctrine\ORM\Mapping\Entity;
use Doctrine\ORM\Mapping\Table;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\OneToMany;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\JoinColumn;
use Doctrine\ORM\PersistentCollection;
use Kdyby\Doctrine;

/**
 * @Entity(repositoryClass="App\Repositories\ParticipantsRepository")
 * @property bool $admin
 * @property Group|null $group
 */
class Participant extends Person {

    /**
     * Je admin skupiny? (muze editovat/vyhodit cleny atd..)
     * @var bool
     * @Column(type="boolean")
     */
    protected $admin = false;

    // Asociace

    /**
     * Skupina do ktere patri
     * @ManyToOne(targetEntity="Group",  inversedBy="participants", cascade={"persist"})
     * @JoinColumn(name="group_id", referencedColumnName="id")
     *
     * @var Group
     **/
    protected $group;



    /**
     * @return bool
     */
    public function isAdmin()
    {
        return $this->admin;
    }

    /**
     * @param bool $admin
     */
    public function setAdmin($admin = true)
    {
        $this->admin = $admin;
    }


    /**
     * @param Group $group
     */
    public function setGroup(Group $group)
    {
        $this->group = $group;
    }

    /**
     * @return Group
     */
    public function getGroup()
    {
        return $this->group;
    }



    public function setConfirmed($confirmed = true)
    {


        if($confirmed == false) {
            $this->confirmed = false;
            $this->paid = false;
            $this->arrived = false;
            $this->left = false;

            if($this->getGroup()->getBoss() === $this) {
                $this->getGroup()->setBoss(null); // zrusime ho jako sefa
                $this->getGroup()->tryDefineBoss(); // zkusime najit jinyho vhodnyho
            }
        } else {
            $this->confirmed = true;
//            if($this->getGroup()->isOverPaid())
//                $this->paid = true;
        }


        return $this;
    }

//    public function setPaid($paid = true) {
//
//        if($paid == true) {
//            $this->paid = true;
//        } else {
//            $this->paid = false;
//        }
//
//        return $this;
//    }



    /**
     * Vrati objekt s nette identitou
     */
    public function toIdentity() {
        return new \Nette\Security\Identity($this->id, Person::ROLE_PARTICIPANT);
    }



}