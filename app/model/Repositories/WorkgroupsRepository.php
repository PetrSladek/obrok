<?php
namespace App\Model\Repositories;

use App\Model\Entity\Workgroup;
use Kdyby\Doctrine\EntityDao;

/**
 * Class WorkgroupsRepository
 * @package App\Model\Repositories
 * @author  peggy <petr.sladek@skaut.cz>
 */
class WorkgroupsRepository extends EntityDao
{
	/**
	 * @param $name
	 *
	 * @return Workgroup
	 */
	public function checkByName($name)
	{
		$item = $this->findOneBy(['name' => $name]);
		if (!$item)
		{
			$item = new Workgroup();
			$item->name = $name;
			$this->_em->persist($item);
		}

		return $item;
	}

}