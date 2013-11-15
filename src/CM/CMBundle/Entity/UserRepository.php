<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\Query;

/**
 * UserRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class UserRepository extends BaseRepository
{
    static protected function getOptions(array $options = array())
    {
        return array_merge(array(
            'group_id'      => null,
            'page_id'       => null,
            'archive'       => null, 
            'paginate'      => true,
            'limit'         => 25
        ), $options);
    }

    public function getCreatedGroupsIds($user_id)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('g.id')->from('CMBundle:Group', 'g')
            ->leftJoin('g.creator', 'c')
            ->where('c.id = :user_id')->setParameter('user_id', $user_id)
            ->getQuery()->getResult();
    }

    public function getCreatedPagesIds($user_id)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('p.id')->from('CMBundle:Page', 'p')
            ->leftJoin('p.creator', 'c')
            ->where('c.id = :user_id')->setParameter('user_id', $user_id)
            ->getQuery()->getResult();
    }

    public function getFromAutocomplete($fullname, $exclude = array())
    {
        $qb = $this->createQueryBuilder('u');
        return $qb->select('partial u.{id, username, firstName, lastName, img, imgOffset}')
            ->andWhere('u.id NOT IN (:exclude)')->setParameter('exclude', $exclude)
            // ->where('u.IsActive = ?', true)
            ->andWhere('u.enabled = '.true)
            ->andWhere(
                $qb->expr()->orX('CONCAT(u.firstName, CONCAT(\' \', u.lastName)) LIKE :fullname', 'CONCAT(u.lastName, CONCAT(\' \', u.firstName)) LIKE :fullname')
            )->setParameter('fullname', $fullname.'%')
            ->setMaxResults(8)
            ->orderBy('u.vip', 'DESC')
            ->getQuery()->getResult(Query::HYDRATE_ARRAY);
    }
}
