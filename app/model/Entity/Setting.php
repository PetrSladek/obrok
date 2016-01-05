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
 * @Entity(repositoryClass="App\Model\Repositories\SettingsRepository")
 * @Table(name="settings")
 * @property string $key
 * @property string $value
 */
class Setting extends Doctrine\Entities\BaseEntity
{

	use \Kdyby\Doctrine\Entities\Attributes\Identifier; // Using Identifier trait for id column

	/**
	 * Klíč
	 * @Column(name="`key`", type="string", length=255)
	 */
	protected $key;

	/**
	 * Hodnota
	 * @Column(type="string", length=255)
	 */
	protected $value;


	/**
	 * Setting constructor.
	 *
	 * @param $key
	 * @param $value
	 */
	public function __construct($key, $value)
	{
		$this->key = $key;
		$this->value = $value;
	}

}