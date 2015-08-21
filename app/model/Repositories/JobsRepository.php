<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 14:21
 */

namespace App\Model\Repositories;


use App\Model\Entity\Job;
use Kdyby\Doctrine\EntityDao;

class JobsRepository extends EntityDao {

    public function checkByName($name) {
        $item = $this->findOneBy(['name'=>$name]);
        if(!$item) {
            $item = new Job();
            $item->name = $name;
            $this->_em->persist($item);
        }
        return $item;
    }

}