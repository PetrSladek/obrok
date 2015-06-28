<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 20.6.2015
 * Time: 10:27
 */

namespace App\Model;


class Phone {

    public $countryCode;

    public $number;

    public function __construct($phone)
    {
        $phone = trim(str_replace(" ","", (string) $phone));
        $this->countryCode = substr($phone, 0, -9);
        $this->number = (int) substr($phone, -9);
    }

    public function __toString() {
        return sprintf("%s %s", $this->getCc(), $this->getNumber());
    }


    public function getCc() {
        return $this->countryCode;
    }
    public function getNumber() {
        return number_format($this->number, 0, '.', ' ');
    }


}