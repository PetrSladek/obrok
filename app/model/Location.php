<?php

namespace App\Model;

/**
 * Class Address
 *
 * @package App\Model
 *
 * @author  peggy <petr.sladek@skaut.cz>
 */
class Location
{

	/**
	 * Lat
	 *
	 * @var float
	 */
	public $lat;

	/**
	 * Lng
	 *
	 * @var float
	 */
	public $lng;


	/**
	 * Location constructor.
	 *
	 * @param $lat
	 * @param $lng
	 */
	public function __construct($lat, $lng)
	{
		$this->lat = $lat;
		$this->lng = $lng;
	}
	

	/**
	 * Převede adresi na string
	 *
	 * @return string
	 */
	public function __toString()
	{
		return sprintf('%s, %s', $this->lat, $this->lng);
	}
}