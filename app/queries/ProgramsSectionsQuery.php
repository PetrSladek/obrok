<?php

namespace App\Query;

use App\Model\Entity\Participant;
use App\Model\Entity\Program;
use App\Model\Entity\ProgramSection;
use Kdyby\Persistence\Queryable;

/**
 * Class ProgramsSectionsQuery
 *
 * @package App\Query
 * @see     https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ProgramsSectionsQuery extends BaseQuery
{

	/**
	 * Připojí načtení programů
	 * 
	 * @return $this
	 */
	public function withPrograms()
	{
		$this->onPostFetch[] = function ($_, Queryable $repository, \Iterator $iterator)
		{
			$ids = array_map(function(ProgramSection $section) { return $section->getId(); }, iterator_to_array($iterator));

			$repository->createQueryBuilder()
				->select('partial section.{id}', 'programs')
				->from(ProgramSection::class, 'section')
				->leftJoin('section.programs', 'programs')
				->andWhere('section.id IN (:ids)')->setParameter('ids', $ids)
				->getQuery()->getResult();


		};

		return $this;
	}

	/**
	 * Připojí načtení proxy attendies
	 *
	 * @return $this
	 */
	public function withAttendies()
	{
		$this->onPostFetch[] = function ($_, Queryable $repository, \Iterator $iterator)
		{
			$ids = array_map(function(ProgramSection $section) { return $section->getId(); }, iterator_to_array($iterator));

			$repository->createQueryBuilder()
				->select('partial section.{id}', 'partial programs.{id}', 'partial attendees.{id}')
				->from(ProgramSection::class, 'section')
				->leftJoin('section.programs', 'programs')
				->leftJoin('programs.attendees', 'attendees')
				->andWhere('section.id IN (:ids)')->setParameter('ids', $ids)
				->getQuery()->getResult();

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
						 ->select('s')// section
						 ->from(ProgramSection::class, 's');

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