<?php

namespace App\Query;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\ORM\Query\Expr\Join;
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb) use ($id)
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
		$this->filter[__METHOD__] = function (QueryBuilder $qb) use ($name)
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
		$this->filter[__METHOD__] = function (QueryBuilder $qb) use ($region)
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
		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.paid = :paid')
			   ->setParameter('paid', true);
		};

		return $this;
	}

    /**
     * Najde pouze zaplacene
     *
     * @return $this
     */
    public function onlyUnpaid()
    {

        $this->filter[__METHOD__] = function (QueryBuilder $qb)
        {
            $qb->andWhere('g.paid = :paid')
                ->setParameter('paid', false);
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
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

		$this->filter[__METHOD__] = function (QueryBuilder $qb)
		{
			$qb->andWhere('g.left = :left')
			   ->setParameter('left', false);
		};

		return $this;
	}


    /**
     * Připojí ke skupině i její aktivní uživatele
     *
     * @return $this
     */
	public function withParticipants()
    {
        $this->select[__METHOD__] = function (QueryBuilder $qb)
        {
            $qb->addSelect('COUNT(p) AS HIDDEN  participantsCount');
            $qb->leftJoin('g.participants', 'p', Join::WITH, 'p.confirmed = 1');
        };

        return $this;
    }


    /**
     * Vybere jen skupiny z poctem ucastniku mensim nez je zadan pocet
     *
     * @param int $count
     *
     * @return $this
     */
    public function hasCountParticipantsLessThen($count)
    {
        // musime do selectu pridat ucastniky
        $this->withParticipants();

        $this->filter[__METHOD__] = function (QueryBuilder $qb) use ($count)
        {
            $qb->andHaving('participantsCount < :ltCount')
               ->setParameter(':ltCount', $count);
        };

        return $this;
    }

    /**
     * Vybere jen skupiny z poctem ucastniku alespoň ...
     *
     * @param int $count
     *
     * @return $this
     */
    public function hasCountParticipantsAtLeast($count)
    {
        // musime do selectu pridat ucastniky
        $this->withParticipants();

        $this->filter[__METHOD__] = function (QueryBuilder $qb) use ($count)
        {
            $qb->andHaving('participantsCount >= :atlCount')
                ->setParameter(':atlCount', $count);
        };

        return $this;
    }

    /**
     * Vyfiltruje jen skupiny co mají nějaké potvrzené ale nezaplacené účastníky
     */
    public function hasUnpaidParticipants()
    {
        $this->filter[__METHOD__] = function (QueryBuilder $qb)
        {
            $sqb = clone $qb;
            $sqb->resetDQLPart('from');
            $sqb->select('up') // unpaid participant
                ->from(Participant::class, 'up')
                ->where("up.group = g AND up.confirmed = 1 AND up.paid = 0");

            $qb->andWhere('EXISTS (' . $sqb->getDQL() . ')');
        };

        return $this;
    }

    /**
     * Vyfiltruje jen skupiny co mají nějaké potvrzené a zaplacené účastníky
     */
    public function hasPaidParticipants()
    {
        $this->filter[__METHOD__] = function (QueryBuilder $qb)
        {
            $sqb = clone $qb;
            $sqb->resetDQLPart('from');
            $sqb->select('pp') // unpaid participant
                ->from(Participant::class, 'pp')
                ->where("pp.group = g AND  pp.confirmed = 1 AND pp.paid=1");

            $qb->andWhere('EXISTS (' . $sqb->getDQL() . ')');
        };

        return $this;
    }

    /**
     * Vybere jen skupiny nemající žádného šéfa
     *
     * @return $this
     */
    public function hasNoBoss()
    {
        $this->filter[__METHOD__] = function (QueryBuilder $qb)
        {
            $qb->andWhere('g.boss IS NULL');
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
						 ->select('g')// group
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
        $qb->groupBy('g.id');
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

		return $qb->select('COUNT(DISTINCT g.id)');
	}

}