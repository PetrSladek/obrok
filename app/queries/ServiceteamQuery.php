<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 21:44
 */

namespace App\Query;


use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;
use Nette\Utils\DateTime;


/*
 * @see https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ServiceteamQuery extends PersonsQuery {




    public function onlyPaid()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.paid = :paid')->setParameter('paid', true);
        };
        return $this;
    }

    public function onlyNotPaid()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.paid = :paid')->setParameter('paid', false);
        };
        return $this;
    }





    public function searchWorkgroupOrJob($q)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($q) {
            $qb->andWhere('(j.name LIKE :q OR w.name LIKE :q)')
                ->setParameter('q', "%$q%");
        };
        return $this;
    }

    /**
     * Je v jednom z techto tymu
     * @param Team[]|array $teams
     * @return $this
     */
    public function inTeams($teams)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($teams) {
            $qb->andWhere('p.team IN(:teams)')->setParameter('teams', $teams);
        };
        return $this;
    }

    /**
     * Prida do selektu Tym
     * @return $this
     */
    public function withTeam()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('t')
                ->leftJoin('p.team', 't');
        };
        return $this;
    }

    /**
     * Prida do selektu Pracovni skupinu
     * @return $this
     */
    public function withWorkgroup()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('w')
                ->leftJoin('p.workgroup', 'w');
        };
        return $this;
    }

    /**
     * Prida do selektu pracovni pozici
     * @return $this
     */
    public function withJob()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('j')
                ->leftJoin('p.job', 'j');
        };
        return $this;
    }




    /**
     * Najde podle variabilniho symoblu nebo ID
     * @param $varSymbol
     * @return $this
     */
    public function byVarSymbol($varSymbol)
    {
        // zkuzsi zjistit varSymbol z ID
        $id = Serviceteam::getIdFromVarSymbol($varSymbol);
        // pokud to neni var symbol tak jde o ID
        if($id == null)
            $id = $varSymbol;

        return $this->byId($id);
    }



    protected function createBasicDql(Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('p') // person
            ->from(Serviceteam::class, 'p');

        $this->applyFilterTo($qb);

        return $qb;
    }

}