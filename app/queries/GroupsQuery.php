<?php

namespace App\Query;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;
use Nette\Utils\DateTime;

/**
 * Class GroupsQuery
 * @package App\Query
 * @see     https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class GroupsQuery extends BaseQuery
{

	/**
	 * Najde podle variabilniho symoblu nebo ID
	 *
	 * @param $varSymbol
	 *
	 * @return $this
	 */
	public function byVarSymbol($varSymbol)
	{
		// zkuzsi zjistit varSymbol z ID
		$id = Group::getIdFromVarSymbol($varSymbol);
		// pokud to neni var symbol tak jde o ID
		if ($id == null)
		{
			$id = $varSymbol;
		}

		return $this->byId($id);
	}


	/**
	 * Najde podle ID
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
			$qb->andWhere('g.id = :id')
			   ->setParameter('id', $id);
		};

		return $this;
	}


	/**
	 * Najde podle jména
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
				->andWhere('CONCAT( CONCAT(IFNULL(g.name, \'\'), \' \'), IFNULL(g.city, \'\')) LIKE :name')
				->setParameter('name', "%$name%");
		};

		return $this;
	}


	/**
	 * Najde podle kraje
	 *
	 * @param $region
	 *
	 * @return $this
	 */
	public function searchRegion($region)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($region)
		{
			$qb
				->andWhere('g.region LIKE :region')
				->setParameter('region', "%$region%");
		};

		return $this;
	}


	/**
	 * Najde pouze potvrzene
	 *
	 * (maji alespon jednoho potvrzeneho ucastnika)
	 *
	 * @return $this
	 */
	public function onlyConfirmed()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.confirmed = :confirmed')
			   ->setParameter('confirmed', true);
		};

		return $this;
	}


	/**
	 * Najde pouze ne potvrzene
	 *
	 * (nemaji zadneho potvrzeneho ucastnika)
	 *
	 * @return $this
	 */
	public function onlyNotConfirmed()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.confirmed = :confirmed')
			   ->setParameter('confirmed', false);
		};

		return $this;
	}


	/**
	 * Najde pouze zaplacene
	 *
	 * @return $this
	 */
	public function onlyPaid()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.paid = :paid')
			   ->setParameter('paid', true);
		};

		return $this;
	}


	/**
	 * Najde pouze nezaplacene zaplacene
	 *
	 * @return $this
	 */
	public function onlyNotPaid()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.paid = :paid')
			   ->setParameter('paid', false);
		};

		return $this;
	}


	/**
	 * Najde pouze prijete
	 *
	 * @return $this
	 */
	public function onlyArrived()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.arrived = :arrived')
			   ->setParameter('arrived', true);
		};

		return $this;
	}

	/**
	 * Najde pouze neprijete
	 *
	 * @return $this
	 */
	public function onlyNotArrived()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.arrived = :arrived')
			   ->setParameter('arrived', false);
		};

		return $this;
	}


	/**
	 * Najde pouze odjete
	 * @return $this
	 */
	public function onlyLeft()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.left = :left')
			   ->setParameter('left', true);
		};

		return $this;
	}

	/**
	 * Najde pouze neodjete
	 * @return $this
	 */
	public function onlyNotLeft()
	{

		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.left = :left')
			   ->setParameter('left', false);
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
						 ->select('g')// person
						 ->from(Group::class, 'g');

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

		return $qb->select('COUNT(g.id)');
	}

}