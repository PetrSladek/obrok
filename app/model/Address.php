<?php

namespace App\Model;

/**
 * Class Address
 * @package App\Model
 * @author  peggy <petr.sladek@skaut.cz>
 */
class Address
{

	/**
	 * Ulice
	 * @var string|null
	 */
	public $street;

	/**
	 * Mesto
	 * @var string|null
	 */
	public $city;

	/**
	 * PSČ
	 * @var string|null
	 */
	public $postalCode;

	/**
	 * Země
	 * @var string
	 */
	public $country = 'Česká republika';


	/**
	 * Address constructor.
	 *
	 * @param null|string $street
	 * @param null|string $city
	 * @param null|string $postalCode
	 * @param null|string $country
	 */
	public function __construct($street = null, $city = null, $postalCode = null, $country = null)
	{
		if ($street)
		{
			$this->street = $street;
		}
		if ($city)
		{
			$this->city = $city;
		}
		if ($postalCode)
		{
			$this->postalCode = $postalCode;
		}
		if ($country)
		{
			$this->country = $country;
		}
	}

}