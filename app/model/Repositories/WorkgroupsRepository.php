<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 14:21
 */

namespace App\Repositories;


use App\Model\Entity\Workgroup;
use Kdyby\Doctrine\EntityDao;

class WorkgroupsRepository extends EntityDao {

    public function checkByName($name) {
        $item = $this->findOneBy(['name'=>$name]);
        if(!$item) {
            $item = new Workgroup();
            $item->name = $name;
            $this->_em->persist($item);
        }
        return $item;
    }

}