<?php

namespace App\Model\Entity;

/**
 * Entita s Avatarem
 *
 * @author  psl <petr.sladek@webnode.com>
 */
trait Avatarable
{
	/**
	 * Avatar jmeno souboru
	 *
	 * @Column(type="string", length=1024, nullable=true)
	 * @var string|null
	 */
	protected $avatar;

	/**
	 * Avatar oriznutí
	 *
	 * @Column(type="json_array", nullable=true)
	 * @var int[]|null
	 */
	protected $avatarCrop;


	/**
	 * Nastav název souboru s avatarem
	 *
	 * @param string|null $avatar
	 */
	public function setAvatar($avatar)
	{
		$this->avatar = $avatar;
	}


	/**
	 * Vrať název souboru s avatarem
	 *
	 * @return string|null
	 */
	public function getAvatar()
	{
		return $this->avatar;
	}


	/**
	 * Nastav ořez originálu avataru
	 *
	 * @return int[]|null
	 */
	public function getAvatarCrop()
	{
		return $this->avatarCrop;
	}


	/**
	 * Nastav ořez originálu avataru
	 *
	 * @param int[]|null $crop
	 */
	public function setAvatarCrop($crop)
	{
		$this->avatarCrop = $crop;
	}


	/**
	 * Odstraní avatara
	 */
	public function removeAvatar()
	{
		$this->avatar = null;
		$this->avatarCrop = null;
	}
}