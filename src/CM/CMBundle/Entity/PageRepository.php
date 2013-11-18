<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * EventRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class PageRepository extends BaseRepository
{
    static protected function getOptions(array $options = array())
    {
        return array_merge(array(
        ), $options);
    }

    public function getAdmins($pageId)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from('CMBundle:User', 'u')
            ->leftJoin('u.userPages', 'up')
            ->where('up.admin = '.true)
            ->andWhere('identity(up.page) = :page_id')->setParameter('page_id', $pageId)
            ->getQuery()->getResult();
    }

    public function filterPagesForUser($userId)
    {
        return $this->createQueryBuilder('p')
            ->select('p')
            ->leftJoin('p.pageUsers', 'pu')
            ->where('pu.user = :user_id')->setParameter('user_id', $userId);
    }

    public function getPagesForUser($userId)
    {
        return $this->filterPagesForUser($userId)->getQuery()->getResult();
    }

    public function getUserIdsFor($pageId, $excludes = null)
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('DISTINCT u.id')->from('CMBundle:User', 'u')
            ->leftJoin('u.userPages', 'ug')
            ->where('ug.page = :page_id')->setParameter('page_id', $pageId);
        if (count($excludes) > 0) {
            $query->andWhere('u.id NOT IN (:excludes)')->setParameter('excludes', $excludes);
        }
        return array_map(function ($user) { return $user['id']; }, $query->getQuery()->getResult());
    }
}