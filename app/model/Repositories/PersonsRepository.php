<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 14:21
 */

namespace App\Model\Repositories;


use App\Model\Entity\Person;
use Kdyby\Doctrine\EntityDao;

class PersonsRepository extends EntityDao {

    public function changePersonTypeTo(Person &$entity, $type) {
        $em = $this->getEntityManager();

        $table = $this->getClassMetadata()->getTableName();
        $em->getConnection()->executeUpdate("UPDATE {$table} SET type = ? WHERE id = ? LIMIT 1", [$type, $entity->id]);
        $em->clear(Person::class);

        // vratim parametrem
        $entity = $this->find($entity->id);
    }

}