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
        $options = array_merge(array(
            'locale'        => 'en'
        ), $options);
        
        return array_merge(array(
            'page_id'       => null,
            'archive'       => null, 
            'paginate'      => true,
            'limit'         => 25,
            'tags'          => false,
            'biography'     => false,
            'locales'       => array_values(array_merge(array('en' => 'en'), array($options['locale'] => $options['locale']))),
        ), $options);
    }

    public function getUserBySlug($slug, array $options = array())
    {
        $query = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.enabled = '.true); 
                       
        if ($options['tags']) {
            $query->addSelect('ut, t, tt')
                ->leftJoin('u.userTags', 'ut', '', '', 'ut.order')
                ->leftJoin('ut.tag', 't')
                ->leftJoin('t.translations', 'tt', 'with', 'tt.locale = :locale')->setParameter('locale', $options['locale']);
        }
        
        $query->andWhere('u.usernameCanonical = :slug')->setParameter('slug', $slug);
        return $query->getQuery()->getSingleResult();
    }

    public function getAdminPagesIds($user_id)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p.id')->from('CMBundle:Page', 'p')
            ->leftJoin('p.pageUsers', 'pu')
            ->leftJoin('pu.user', 'u')
            ->where('pu.userId = :user_id')->setParameter('user_id', $user_id)
            ->andWhere('u.enabled = '.true)
            ->andWhere('pu.admin = '.true)
            ->getQuery()->getArrayResult();
        return array_map('current', $query);
    }

    public function getFromAutocomplete($fullname, $userId, $exclude = array())
    {
        $relations = $this->getEntityManager()->createQueryBuilder()
            ->select('partial r.{id, userId}')
            ->from('CMBundle:Relation', 'r')
            ->where('r.fromUserId = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted = :accepted')->setParameter('accepted', Relation::ACCEPTED_BOTH)
            ->groupBy('r.userId')
            ->getQuery()->getArrayResult();

        if (count($relations) == 0) return array();

        $relations = array_map(function($v) { return $v['userId']; }, $relations);

        $qb = $this->createQueryBuilder('u');
        return $qb->select('partial u.{id, username, usernameCanonical, firstName, lastName, img, imgOffset}')
            ->andWhere('u.id IN (:relations)')->setParameter('relations', $relations)
            ->andWhere('u.id NOT IN (:exclude)')->setParameter('exclude', $exclude)
            ->andWhere('u.enabled = '.true)
            ->andWhere(
                $qb->expr()->orX('CONCAT(u.firstName, CONCAT(\' \', u.lastName)) LIKE :fullname', 'CONCAT(u.lastName, CONCAT(\' \', u.firstName)) LIKE :fullname')
            )->setParameter('fullname', $fullname.'%')
            ->setMaxResults(8)
            ->getQuery()->getArrayResult();
    }

    public function getLastRegisteredUsers($options = array())
    {
        $options = self::getOptions($options);
        
        if ($options['paginate']) {
            $count = $this->createQueryBuilder('u')
                ->select('count(u.id)')
                ->andWhere('u.enabled = '.true);
        }
        
        $query = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.enabled = '.true)
            ->andWhere('u.img is not null')
            ->orderBy('u.id', 'desc');
            
        if ($options['tags']) {
            $query->addSelect('ut, t, tt')
                ->leftJoin('u.userTags', 'ut', '', '', 'ut.order')
                ->leftJoin('ut.tag', 't')
                ->leftJoin('t.translations', 'tt', 'with', 'tt.locale = :locale');
        }
            
        if ($options['biography']) {
            $query->addSelect('b, bt')
                ->leftJoin('u.biography', 'b')
                ->leftJoin('b.translations', 'bt', 'with', 'bt.locale in (:locales)')->setParameter('locales', $options['locales']);
        }
        
        if ($options['tags'] or $options['biography']) {
            $query->setParameter('locale', $options['locale']);
        }
            
        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function getWith($id, $what = array())
    {
        $query = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.enabled = '.true)
            ->andWhere('u.id = :id')->setParameter('id', $id);
            foreach ($what as $prop) {
                $query->leftJoin('u.'.$prop, $prop);
            }
        return $query->getQuery()->getSingleResult();
    }

    public function getWithTags($id, $options = array())
    {
        $query = $this->createQueryBuilder('u')
            ->select('u, ut, t, tt')
            ->andWhere('u.enabled = '.true)
            ->leftJoin('u.userTags', 'ut', '', '', 'ut.order')
            ->leftJoin('ut.tag', 't')
            ->leftJoin('t.translations', 'tt', 'with', 'tt.locale = :locale')
            ->setParameter('locale', $options['locale'])
            ->andWhere('u.id = :id')->setParameter('id', $id);
        return $query->getQuery()->getSingleResult();
    }

    public function search($q, $limit)
    {
        $qb = $this->createQueryBuilder('u');
        return $qb->select('u')
            ->andWhere('u.enabled = '.true)
            ->andWhere(
                $qb->expr()->orX(
                    $qb->expr()->orX('CONCAT(u.firstName, CONCAT(\' \', u.lastName)) LIKE :query', 'CONCAT(u.lastName, CONCAT(\' \', u.firstName)) LIKE :query'),
                    'CONCAT(u.lastName, CONCAT(\' \', u.firstName)) LIKE :Squery'
                )
            )->setParameter('query', $q.'%')
            ->setParameter('Squery', '% '.$q.'%')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function getFaces($options = array())
    {
        $options = self::getOptions($options);
        
        $query = $this->createQueryBuilder('u')
            ->select('u')
            ->andWhere('u.enabled = '.true)
            ->where('u.img is not null')
            ->orderBy('u.id', 'desc');
            
        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }
}
