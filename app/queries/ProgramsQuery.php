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
use App\Model\Entity\Program;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\GeneratedProxy\__CG__\App\Model\Entity\ProgramSection;
use Kdyby\Persistence\Queryable;
use Nette\Utils\DateTime;

/**
 * Class ProgramsQuery
 * @package App\Query
 * @see https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ProgramsQuery extends BaseQuery {


    public function byId($id) {
        $id = (int) str_replace("#",'',$id);

        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $id);
        };
        return $this;
    }

    /**
     * Je v jednom z techto sekci
     * @param ProgramSection[]|array $teams
     * @return $this
     */
    public function inSections($sections)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($sections) {
            $qb->andWhere('p.section IN(:sections)')->setParameter('sections', $sections);
        };
        return $this;
    }


    public function searchName($name) {
        $this->filter[] = function (QueryBuilder $qb) use ($name) {
            $qb
                ->andWhere('CONCAT( CONCAT(IFNULL(p.name, \'\'), \' \'), IFNULL(p.lector, \'\')) LIKE :name')
                ->setParameter('name', "%$name%");
        };

        return $this;
    }



    public function onlyNotFull() {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->leftJoin('p.attendees', 'a')
                ->andHaving('COUNT(a.id) < p.capacity');
        };

        return $this;
    }
    public function onlyFull() {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->leftJoin('p.attendees', 'a')
                ->andHaving('COUNT(a.id) >= p.capacity');
        };

        return $this;
    }




    protected function createBasicDql(Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('p') // person
            ->from(Program::class, 'p');

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

        return $qb->select('COUNT(p.id)');
    }

}