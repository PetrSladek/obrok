<?php

namespace App\Model;

/**
 * Class Address
 * @package App\Model
 * @author  peggy <petr.sladek@skaut.cz>
 */
class Address
{

	public $street;

	public $city;

	public $postalCode;

	public $country = 'Česká republika';


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