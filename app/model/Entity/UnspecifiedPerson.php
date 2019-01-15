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
 * @Entity(repositoryClass="App\Model\Repositories\UnspecifiedPersonsRepository")
 */
class UnspecifiedPerson extends Person
{

	/**
	 * Byl odeslán email s platebními informacemi
	 *
	 * @var bool
	 *
	 * @Column(type="boolean")
	 */
	protected $sentPaymentInfoEmail = false;


	/**
	 * Vrati objekt s nette identitou
	 */
	public function toIdentity()
	{
		return new \Nette\Security\Identity($this->getId(), Person::TYPE_UNSPECIFIED);
	}

	/**
	 * @return bool
	 */
	public function isSentPaymentInfoEmail(): bool
	{
		return $this->sentPaymentInfoEmail;
	}

	/**
	 * @param bool $sentPaymentInfoEmail
	 */
	public function setSentPaymentInfoEmail(bool $sentPaymentInfoEmail): void
	{
		$this->sentPaymentInfoEmail = $sentPaymentInfoEmail;
	}


}