<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 20.6.2015
 * Time: 10:27
 */

namespace App\Model;


class Address {

    public $street;

    public $city;

    public $postalCode;

    public $country = 'Česká republika';


    public function __construct($street = null, $city = null, $postalCode = null, $country = null)
    {
        if($street)
            $this->street = $street;
        if($city)
            $this->city = $city;
        if($postalCode)
            $this->postalCode = $postalCode;
        if($country)
            $this->country = $country;
    }


}