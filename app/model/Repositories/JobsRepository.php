<?php

namespace App\Model\Repositories;

use App\Model\Entity\Job;
use Kdyby\Doctrine\EntityDao;

/**
 * Class JobsRepository
 * @package App\Model\Repositories
 * @author  peggy <petr.sladek@skaut.cz>
 */
class JobsRepository extends EntityDao
{

	public function checkByName($name)
	{
		$item = $this->findOneBy(['name' => $name]);
		if (!$item)
		{
			$item = new Job();
			$item->name = $name;
			$this->_em->persist($item);
		}

		return $item;
	}

}