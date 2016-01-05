<?php

namespace App\Model;

/**
 * Class Phone
 * @package App\Model
 * @author  peggy <petr.sladek@skaut.cz>
 */
class Phone
{
	/**
	 * @var string
	 */
	public $countryCode;

	/**
	 * @var int
	 */
	public $number;


	/**
	 * Phone constructor.
	 *
	 * @param $phone
	 */
	public function __construct($phone)
	{
		$phone = trim(str_replace(" ", "", (string) $phone));
		$this->countryCode = substr($phone, 0, -9);
		$this->number = (int) substr($phone, -9);
	}


	/**
	 * @return string
	 */
	public function __toString()
	{
		return sprintf("%s %s", $this->getCc(), $this->getNumber());
	}


	/**
	 * @return string
	 */
	public function getCc()
	{
		return $this->countryCode;
	}


	/**
	 * @return string
	 */
	public function getNumber()
	{
		return number_format($this->number, 0, '.', ' ');
	}

}