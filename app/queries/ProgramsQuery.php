<?php

namespace App\Query;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;
use Nette\Utils\DateTime;

/**
 * Class ProgramsQuery
 * @package App\Query
 * @see     https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ProgramsQuery extends BaseQuery
{

	/**
	 * Podle ID
	 *
	 * @param $id
	 *
	 * @return $this
	 */
	public function byId($id)
	{
		$id = (int) str_replace("#", '', $id);

		$this->filter[] = function (QueryBuilder $qb) use ($id)
		{
			$qb->andWhere('p.id = :id')
			   ->setParameter('id', $id);
		};

		return $this;
	}


	/**
	 * Je v jednom z techto sekci
	 *
	 * @param ProgramSection[]|array $sections
	 *
	 * @return $this
	 */
	public function inSections($sections)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($sections)
		{
			$qb->andWhere('p.section IN(:sections)')->setParameter('sections', $sections);
		};

		return $this;
	}


	/**
	 * Vyhleda podle jmena
	 *
	 * @param $name
	 *
	 * @return $this
	 */
	public function searchName($name)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($name)
		{
			$qb
				->andWhere('CONCAT( CONCAT(IFNULL(p.name, \'\'), \' \'), IFNULL(p.lector, \'\')) LIKE :name')
				->setParameter('name', "%$name%");
		};

		return $this;
	}


	/**
	 * Jen programy co maji volno
	 *
	 * @return $this
	 */
	public function onlyNotFull()
	{
		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('SIZE(p.attendees) < p.capacity');
		};

		return $this;
	}


	/**
	 * Jen plne programy
	 *
	 * @return $this
	 */
	public function onlyFull()
	{
		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('SIZE(p.attendees) >= p.capacity');
		};

		return $this;
	}


	/**
	 * @param Queryable $repository
	 *
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	protected function createBasicDql(Queryable $repository)
	{
		$qb = $repository->createQueryBuilder()
						 ->select('p') // program
						 ->from(Program::class, 'p');

		$this->applyFilterTo($qb);

		return $qb;
	}


	/**
	 * @param \Kdyby\Persistence\Queryable $repository
	 *
	 * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
	 */
	protected function doCreateQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository);
		$this->applySelectTo($qb);

		return $qb;
	}


	/**
	 * @param Queryable $repository
	 *
	 * @return \Kdyby\Doctrine\QueryBuilder
	 */
	protected function doCreateCountQuery(Queryable $repository)
	{
		$qb = $this->createBasicDql($repository);
		$this->applySelectTo($qb);

		return $qb->select('COUNT(p.id)');
	}

}