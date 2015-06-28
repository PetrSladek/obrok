<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 21:44
 */

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
 * @see https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class GroupsQuery extends BaseQuery {



    /**
     * Najde podle variabilniho symoblu nebo ID
     * @param $varSymbol
     * @return $this
     */
    public function byVarSymbol($varSymbol)
    {
        // zkuzsi zjistit varSymbol z ID
        $id = Group::getIdFromVarSymbol($varSymbol);
        // pokud to neni var symbol tak jde o ID
        if($id == null)
            $id = $varSymbol;

        return $this->byId($id);
    }

    public function byId($id) {
        $id = (int) str_replace("#",'',$id);

        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('g.id = :id')
                ->setParameter('id', $id);
        };
        return $this;
    }


    public function searchName($name) {
        $this->filter[] = function (QueryBuilder $qb) use ($name) {
            $qb
                ->andWhere('CONCAT( CONCAT(IFNULL(g.name, \'\'), \' \'), IFNULL(g.city, \'\')) LIKE :name')
                ->setParameter('name', "%$name%");
        };

        return $this;
    }
    public function searchRegion($region) {
        $this->filter[] = function (QueryBuilder $qb) use ($region) {
            $qb
                ->andWhere('g.region LIKE :region')
                ->setParameter('region', "%$region%");
        };

        return $this;
    }


    public function onlyConfirmed() {

        $this->filter[] = function (QueryBuilder $qb) {
            $qb->innerJoin('g.participants', 'p')
                ->andWhere('p.confirmed = :confirmed')
                ->andHaving('COUNT(p.id) > 0')
                ->setParameter('confirmed', true);
        };

        return $this;
    }

    public function onlyPaid() {

        $this->filter[] = function (QueryBuilder $qb) {
            $qb->innerJoin('g.participants', 'p')
                ->andWhere('p.confirmed = :confirmed')
                ->andHaving('SUM(p.confirmed) = SUM(p.paid)')
                ->setParameter('confirmed', true);
        };

        return $this;
    }

    public function onlyArrived() {

        $this->filter[] = function (QueryBuilder $qb) {
            $qb->innerJoin('g.participants', 'p')
                ->andWhere('p.confirmed = :confirmed')
                ->andHaving('SUM(p.confirmed) = SUM(p.arrived)')
                ->setParameter('confirmed', true);
        };

        return $this;
    }

    public function onlyLeft() {

        $this->filter[] = function (QueryBuilder $qb) {
            $qb->innerJoin('g.participants', 'p')
                ->andWhere('p.confirmed = :confirmed')
                ->andHaving('SUM(p.confirmed) = SUM(p.left)')
                ->setParameter('confirmed', true);
        };

        return $this;
    }






    protected function createBasicDql(Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('g') // person
            ->from(Group::class, 'g');

        $this->applyFilterTo($qb);

        return $qb;
    }

    /**
     * @param \Kdyby\Persistence\Queryable $repository
     * @return \Doctrine\ORM\Query|\Doctrine\ORM\QueryBuilder
     */
    protected function doCreateQuery(Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);
        $this->applySelectTo($qb);

        return $qb;
    }



    protected function doCreateCountQuery(Queryable $repository)
    {
        $qb = $this->createBasicDql($repository);
        $this->applySelectTo($qb);

        return $qb->select('COUNT(g.id)');
    }

}