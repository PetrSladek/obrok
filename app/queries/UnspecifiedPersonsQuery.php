<?php

namespace App\Query;

use App\Model\Entity\Group;
use App\Model\Entity\Participant;
use App\Model\Entity\UnspecifiedPerson;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Persistence\Queryable;

/**
 * Class UnspecifiedPersonsQuery
 * @package App\Query
 * @see     https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class UnspecifiedPersonsQuery extends PersonsQuery
{

	/**
	 * @return $this
	 */
	public function onlyNotSentParticipantInfo()
	{
		$this->filter[] = function (QueryBuilder $qb)
		{
			$qb->andWhere('p.sentPaymentInfoEmail = :sentPaymentInfoEmail')
			   ->setParameter('sentPaymentInfoEmail', false);
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
						 ->from(UnspecifiedPerson::class, 'p');

		$this->applyFilterTo($qb);

		return $qb;
	}

}