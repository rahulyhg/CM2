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
class FanRepository extends BaseRepository
{
    public function getItsFans($id, $object = 'User', $limit = null)
    {
        $query = $this->createQueryBuilder('f')
            ->select('f, o');
        switch($object) {
            default:
            case 'User':
                $query->leftJoin('f.user', 'o')
                    ->where('f.userId = :id');
                break;
            case 'Page':
                $query->leftJoin('f.page', 'o')
                    ->where('f.pageId = :id');
                break;
        }
        $query->setParameter('id', $id); // TODO: integrate with clients
        // return UserQuery::create()->
        //  useFanRelatedByFromUserIdQuery()->
        //      filterByUserId($user_id)->
        //  endUse()->
        //  _if($limit)->
        //      limit($limit)->
        //      orderByImg('desc')->
        //  _else()->
        //      leftJoinWithUserTagRel()->
        //      useUserTagRelQuery(null, 'left join')->
        //          leftJoinWithUserTag()->
        //          useUserTagQuery(null, 'left join')->
        //              joinWithI18n()->
        //          endUse()->
        //      endUse()->
        //      leftJoinWithPost()->
        //      usePostQuery(null, 'left join')->
        //          joinWithEntity()->
        //          useEntityQuery()->
        //              joinWithI18n('en')->
        //          endUse()->
        //      endUse()->
        //      addJoinCondition('Post', 'Post.Object = ?', 'biography')->
        //      orderByLastName()->
        //  _endIf()->
        //  joinWithsfGuardUser()->
        //  where('User.Enabled = ?', true)->
        //  where('sfGuardUser.IsActive = ?', true)->
        //  find();
        if (!is_null($limit)) {
            $query->setMaxResults($limit);
        }
        return $query->getQuery()->getResult();
    }
    
    public function countItsFans($id, $object = 'User')
    {
        $query = $this->createQueryBuilder('f')
            ->select('count(f.id)');
        switch($object) {
            default:
            case 'User':
                $query->where('f.userId = :id');
                break;
            case 'Page':
                $query->where('f.pageId = :id');
                break;
        }
        return $query->setParameter('id', $id)
            // leftJoin('f.user', 'u', 'WITH', 'u.enabled ='.true.' AND u.isActive = '.true)
            ->getQuery()->getSingleScalarResult();
    }

    public function getFanOf($userId, $object = 'User')
    {
        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('o');
        switch($object) {
            default:
            case 'User':
                $query->from('CMBundle:User', 'o');
                break;
            case 'Page':
                $query->from('CMBundle:Page', 'o');
                break;
        }
        return $query->leftJoin('o.fans', 'f')
            ->where('f.fromUserId = :user_id')->setParameter('user_id', $userId)
            ->getQuery()->getResult();
    }

    public function checkIfIsFanOf($userId, $fanId, $object = 'User')
    {
        $query = $this->createQueryBuilder('f')
            ->select('count(f.id)')
            ->where('f.fromUserId = :user_id')->setParameter('user_id', $userId);
        switch($object) {
            case 'User':
                $query->andWhere('f.userId = :fan_id');
                break;
            case 'Page':
                $query->andWhere('f.pageId = :fan_id');
                break;
        }
        return $query->setParameter('fan_id', $fanId)->getQuery()->getSingleScalarResult() > 0;
    }

    public function getFans($ids, $fromUser = false)
    {
        $query = $this->createQueryBuilder('f')
            ->select('f')
            ->leftJoin('f.user', 'u')
            ->leftJoin('f.page', 'p');
        if ($fromUser) {
            $query->where('f.fromUserId = :ids');
        } else {
            $query->where('f.id in (:ids)');
        }
        return $query->setParameter('ids', $ids)->getQuery()->getResult();
    }
}