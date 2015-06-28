<?php
/**
 * Created by PhpStorm.
 * User: Peggy
 * Date: 23.6.2015
 * Time: 21:44
 */

namespace App\Query;


use App\Model\Entity\Participant;
use App\Model\Entity\Serviceteam;
use App\Model\Entity\Team;
use Doctrine\ORM\QueryBuilder;
use Kdyby\Doctrine\QueryObject;
use Kdyby\Persistence\Queryable;
use Nette\Utils\DateTime;

/**
 * Class ParticipantsQuery
 * @package App\Query
 * @see https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
class ParticipantsQuery extends PersonsQuery {



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


    public function searchGroup($group) {
        $this->filter[] = function (QueryBuilder $qb) use ($group) {
            $qb
                ->andWhere('CONCAT( CONCAT(IFNULL(g.name, \'\'), \' \'), IFNULL(g.city, \'\')) LIKE :group')
                ->setParameter('group', "%$group%");
        };

        return $this;
    }


    /**
     * Prida do selektu Tym
     * @return $this
     */
    public function withGroup()
    {
        $this->select[] = function (QueryBuilder $qb) {
            $qb->addSelect('g')
                ->leftJoin('p.group', 'g');
        };
        return $this;
    }





    protected function createBasicDql(Queryable $repository)
    {
        $qb = $repository->createQueryBuilder()
            ->select('p') // person
            ->from(Participant::class, 'p');

        $this->applyFilterTo($qb);

        return $qb;
    }

}