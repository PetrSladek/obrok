<?php

namespace App\Query;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Persistence\Queryable;

/**
 * Class ParticipantsQuery
 * @package App\Query
 * @see     https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ParticipantsQuery extends PersonsQuery
{

    /**
     * Jen maturanti
     *
     * @return $this
     */
    public function onlyGraduateStudent()
    {
        $this->filter[] = function (QueryBuilder $qb)
        {
            $qb->andWhere('p.graduateStudent = :graduateStudent')->setParameter('graduateStudent', true);
        };

        return $this;
    }


    /**
     * Jen NE maturanti
     *
     * @return $this
     */
    public function onlyNotGraduateStudent()
    {
        $this->filter[] = function (QueryBuilder $qb)
        {
            $qb->andWhere('p.graduateStudent = :graduateStudent')->setParameter('graduateStudent', false);
        };

        return $this;
    }

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
	 * Najde ucastniky co maji zapsany program
	 *
	 * @param $program
	 *
	 * @return $this
	 */
	public function inProgram($program)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($program)
		{
			$qb->andWhere(':program MEMBER OF p.programs')
			   ->setParameter('program', $program);
		};

		return $this;
	}


	/**
	 * Vyhledá podle názvu skupiny
	 *
	 * @param $group
	 *
	 * @return $this
	 */
	public function searchGroup($group)
	{
		$this->filter[] = function (QueryBuilder $qb) use ($group)
		{
			$qb
				->andWhere('CONCAT(CONCAT(IFNULL(g.name, \'\'), \' \'), IFNULL(g.city, \'\')) LIKE :group')
				->setParameter('group', "%$group%");
		};

		return $this;
	}


	/**
	 * Prida do selektu Tym
	 *
	 * @return $this
	 */
	public function withGroup()
	{
		$this->select[] = function (QueryBuilder $qb)
		{
			$qb->addSelect('g')
			   ->leftJoin('p.group', 'g');
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
						 ->select('p')// person
						 ->from(Participant::class, 'p');

		$this->applyFilterTo($qb);

		return $qb;
	}

}