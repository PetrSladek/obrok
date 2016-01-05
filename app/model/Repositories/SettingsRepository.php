<?php

namespace App\Model\Repositories;

use App\Model\Entity\Job;
use App\Model\Entity\Setting;
use Kdyby\Doctrine\EntityDao;

/**
 * Class SettingsRepository
 * @package App\Model\Repositories
 * @author  peggy <petr.sladek@skaut.cz>
 */
class SettingsRepository extends EntityDao
{
	/**
	 * @param      $key
	 * @param null $default
	 *
	 * @return null
	 */
	public function get($key, $default = null)
	{
		$class = Setting::class;
		$dql = $this->createQuery("SELECT s.value FROM {$class} s WHERE s.key = :key")->setParameter('key', $key);
		try
		{
			return $dql->getSingleScalarResult();
		}
		catch (\Doctrine\ORM\NoResultException $e)
		{
			return $default;
		}
	}


	/**
	 * @param $key
	 * @param $value
	 *
	 * @return mixed
	 */
	public function set($key, $value)
	{
		$em = $this->getEntityManager();

		$updateSuccessful = (bool) $em->createQueryBuilder()
									  ->update(Setting::class, 's')
									  ->set('s.value', ':value')
									  ->where('s.key = :key')
									  ->setParameters(['value' => $value, 'key' => $key])
									  ->getQuery()->getSingleScalarResult();

		if (!$updateSuccessful)
		{
			$setting = new Setting($key, $value);
			$em->persist($setting);
			$em->flush();
		}

		return $value;
	}

}