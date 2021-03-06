<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;

/**
 * DiscRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class DiscRepository extends BaseRepository
{
    static protected function getOptions(array $options = array())
    {
        $options = array_merge(array(
            'locale'        => 'en'
        ), $options);
        
        return array_merge(array(
            'exclude'       => null,
            'userId'       => null,
            'pageId'       => null,
            'paginate'      => true,
            'categoryId' => null,
            'locales'       => array_values(array_merge(array('en' => 'en'), array($options['locale'] => $options['locale']))),
            'protagonists'  => false,
            'limit'         => 25,
        ), $options);
    }
    
    public function getDiscs(array $options = array())
    {
        $options = self::getOptions($options);
                 
        $count = $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->join('d.post', 'p');

        $query = $this->createQueryBuilder('d')
            ->select('d, dt, t, i, p, l, c, u, lu, cu, pg')
            ->leftJoin('d.discTracks','dt')
            ->leftJoin('d.translations', 't', 'with', 't.locale IN (:locales)')->setParameter('locales', $options['locales'])
            ->setParameter('locales', $options['locales'])
            ->leftJoin('d.image', 'i')
            ->join('d.post', 'p')
            ->leftJoin('p.likes', 'l')
            ->leftJoin('p.comments', 'c')
            ->leftJoin('p.user', 'u')
            ->leftJoin('l.user', 'lu')
            ->leftJoin('c.user', 'cu')
            ->leftJoin('p.page', 'pg');
            
        if ($options['protagonists']) {
            $query->addSelect('eu, us')
                ->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->join('eu.user', 'us');
        }
        
        if ($options['userId']) {
            $count->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
            $query->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
        }
        
        if ($options['pageId']) {
            $count->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
            $query->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        
        if ($options['categoryId']) {
            $count->andWhere('d.category = :category_id')
                ->setParameter(':category_id', $options['categoryId']);
            $query->andWhere('d.category = :category_id')
                ->setParameter(':category_id', $options['categoryId']);
        }

        $query->orderBy('t.title');
        
        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function getDisc($id, array $options = array())
    {
        $options = self::getOptions($options);
        
        return $this->createQueryBuilder('d')
            ->select('d, t, ec, ect, dt, i, p, u, pg')
            ->leftJoin('d.translations', 't', 'with', 't.locale IN (:locales)')->setParameter('locales', $options['locales'])
            ->leftJoin('d.category', 'ec')
            ->leftJoin('ec.translations', 'ect', 'with', 'ect.locale = :locale')->setParameter('locale', $options['locale'])
            ->leftJoin('d.discTracks', 'dt')
            ->leftJoin('d.image', 'i')
            ->leftJoin('d.post', 'p')
            ->leftJoin('p.user', 'u')
            ->leftJoin('p.page', 'pg')
            ->andWhere('d.id = :id')->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    public function getLatests($options = array())
    {
        $options = self::getOptions($options);
        
        $count = $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->join('d.post', 'p');

        $query = $this->createQueryBuilder('d')
            ->select('d, t, p, i')
            ->leftJoin('d.translations', 't', 'with', 't.locale IN (:locales)')->setParameter('locales', $options['locales'])
            ->join('d.post', 'p')
            ->leftJoin('d.image', 'i')
            ->orderBy('p.createdAt', 'desc');
        if (!is_null($options['exclude'])) {
            $count->andWhere('d.id != :exclude')->setParameter('exclude', $options['exclude']);
            $query->andWhere('d.id != :exclude')->setParameter('exclude', $options['exclude']);
        }
        if (!is_null($options['userId'])) {
            $count->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
            $query->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
        }
        if (!is_null($options['pageId'])) {
            $count->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
            $query->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }

        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function countLatests($options = array())
    {
        $options = self::getOptions($options);
        
        $query = $this->createQueryBuilder('d')
            ->select('count(d.id)')
            ->join('d.post', 'p')
            ->orderBy('p.createdAt', 'desc');
        if (!is_null($options['userId'])) {
            $query->join('d.entityUsers', 'eu', '', '', 'eu.userId')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
        }
        if (!is_null($options['pageId'])) {
            $query->andWhere('p.pageId = :page_id')
                ->setParameter('page_id', $options['pageId']);
        }

        return $query->getQuery()
            ->getSingleScalarResult();
    }

    public function getTracksPerDisc($id)
    {   
        return $this->getEntityManager()->createQueryBuilder()
            ->select('t')
            ->from('CMBundle:DiscTrack', 't')
            ->where('t.discId = :id')->setParameter('id', $id)
            ->orderBy('t.number')
            ->addOrderBy('t.id')
            ->getQuery()
            ->getResult();
    }

    public function getSponsored(array $options = array())
    {   
        $options = self::getOptions($options);
        
        $sponsored = $this->getEntityManager()->createQueryBuilder()
            ->select('partial s.{id, entityId, views, start, end}, partial d.{id}')
            ->from('CMBundle:Sponsored','s')
            ->join('s.entity', 'd', 'with', 'd instance of '.Disc::className())
            ->andWhere('s.start <= :now')
            ->andWhere('s.end >= :now')
            ->setParameter(':now', new \DateTime)
            ->getQuery()
            ->getResult();

        if (count($sponsored) == 0) return null;

        shuffle($sponsored);
        $sponsored = array_slice($sponsored, 0, $options['limit']);
        
        $this->getEntityManager()->createQueryBuilder()
            ->update('CMBundle:Sponsored', 's')
            ->set('s.views', 's.views + 1')
            ->where('s.id in (:ids)')->setParameter('ids', array_map(function($i) { return $i->getId(); }, $sponsored))
            ->getQuery()->execute();

        return $this->createQueryBuilder('d')
            ->select('d, dt, t, i')
            ->leftJoin('d.discTracks', 'dt')
            ->leftJoin('d.translations', 't', 'with', 't.locale IN (:locales)')->setParameter('locales', $options['locales'])
            ->leftJoin('d.image', 'i')
            ->andWhere('d.id IN (:ids)')->setParameter('ids', array_map(function($i) { return $i->getEntityId(); }, $sponsored))
            ->getQuery()
            ->getResult();
    }
}
