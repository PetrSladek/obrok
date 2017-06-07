<?php
namespace App\Model\Repositories;

use App\Model\Entity\Person;
use Kdyby\Doctrine\EntityDao;

/**
 * Class PersonsRepository
 * @package App\Model\Repositories
 * @author  peggy <petr.sladek@skaut.cz>
 */
class PersonsRepository extends EntityDao
{

	/**
	 * @param Person $entity
	 * @param        $type
	 */
	public function changePersonTypeTo(Person &$entity, $type)
	{
		$em = $this->getEntityManager();
		$em->getUnitOfWork()->removeFromIdentityMap($entity);

		$table = $this->getClassMetadata()->getTableName();
		$em->getConnection()
		   ->executeUpdate("UPDATE {$table} SET type = ? WHERE id = ? LIMIT 1", [$type, $entity->id]);

		// vratim parametrem
		$entity = $this->find($entity->id);
	}


	/**
	 * @param $skautisPersonId
	 *
	 * @return mixed
	 */
	public function findBySkautisPersonId($skautisPersonId)
	{
		return $this->findOneBy(['skautisPersonId' => $skautisPersonId]);
	}
	
	/**
	 * @param $skautisUserId
	 *
	 * @return mixed
	 */
	public function findBySkautisUserId($skautisUserId)
	{
		return $this->findOneBy(['skautisUserId' => $skautisUserId]);
	}

}
