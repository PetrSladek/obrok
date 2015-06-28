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

/**
 * Class PersonsQuery
 * @package App\Query
 * @see https://github.com/kdyby/doctrine/blob/master/docs/en/resultset.md
 */
abstract class PersonsQuery extends BaseQuery {


    public function onlyConfirmed()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.confirmed = :confirmed')->setParameter('confirmed', true);
        };
        return $this;
    }

    public function onlyNotConfirmed()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.confirmed = :confirmed')->setParameter('confirmed', false);
        };
        return $this;
    }

    public function onlyArrived()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.arrived = :arrived')->setParameter('arrived', true);
        };
        return $this;
    }

    public function onlyNotArrived()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.arrived = :arrived')->setParameter('arrived', false);
        };
        return $this;
    }

    public function onlyLeft()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.left = :left')->setParameter('left', true);
        };
        return $this;
    }

    public function onlyNotLeft()
    {
        $this->filter[] = function (QueryBuilder $qb) {
            $qb->andWhere('p.left = :left')->setParameter('left', false);
        };
        return $this;
    }




    public function byId($id) {
        $id = (int) str_replace("#",'',$id);

        $this->filter[] = function (QueryBuilder $qb) use ($id) {
            $qb->andWhere('p.id = :id')
                ->setParameter('id', $id);
        };
        return $this;
    }

    /**
     * @param \Datetime|int $age
     * @param \Datetime|null $inDate
     * @return $this|ServiceteamQuery
     */
    public function byAge($age, $inDate = null) {
        if($age instanceof \DateTime || strtotime($age) !== false) {
            return $this->byBirthdate($age);
        }

        $age = (int) $age;
        $inDate = DateTime::from($inDate);

        $this->filter[] = function (QueryBuilder $qb) use ($age, $inDate) {
            // cele jmeno nebo prezdivka
            $qb->andWhere('TIMESTAMPDIFF(YEAR, p.birthdate, :inDate) = :age')
                ->setParameter('inDate', $inDate)
                ->setParameter('age', $age);
        };

        return $this;
    }

    /**
     * @param \Datetime|string $birthdate
     * @return $this
     */
    public function byBirthdate($birthdate)
    {
        $birthdate = DateTime::from($birthdate);
        $this->filter[] = function (QueryBuilder $qb) use ($birthdate) {
            $qb->andWhere('p.birthdate = :birthdate')
                ->setParameter('birthdate', $birthdate);
        };
        return $this;
    }

    public function searchFullname($fullname)
    {


        $this->filter[] = function (QueryBuilder $qb) use ($fullname) {
            // cele jmeno nebo prezdivka
            $qb->andWhere('(CONCAT(CONCAT(IFNULL(p.firstName, \'\'), \' \'), IFNULL(p.lastName, \'\')) LIKE :fullname OR p.nickName LIKE :fullname)')
                ->setParameter('fullname', "%$fullname%");
        };
        return $this;
    }

    public function searchAddress($address)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($address) {
            // cast addressy
            $qb->andWhere('CONCAT( CONCAT( CONCAT(IFNULL(p.addressStreet, \'\'), \' \'), CONCAT(IFNULL(p.addressCity, \'\'),  \' \')), IFNULL(p.addressPostcode, \'\')) LIKE :address')
                ->setParameter('address', "%$address%");
        };
        return $this;
    }

    public function searchContact($contact)
    {
        $this->filter[] = function (QueryBuilder $qb) use ($contact) {
            // email nebo telefon
            $qb->andWhere('(p.email LIKE :contact OR p.phone LIKE :contact)')
                ->setParameter('contact', "%$contact%");
        };
        return $this;
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

    abstract protected function createBasicDql(Queryable $repository);

}