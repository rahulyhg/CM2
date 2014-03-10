<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;
use Doctrine\ORM\Query\ResultSetMapping;

/**
 * RelationRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class RelationRepository extends BaseRepository
{
    static protected function getOptions(array $options = array())
    {
        return array_merge(array(
            'relationTypeId' => null,
            'indexBy' => null,
            'groupBy' => null,
            'paginate'      => true,
            'limit'         => null,
        ), $options);
    }
    
    public function getRelationTypesBetweenUsers($userFromId, $userId)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('rt, r')
            ->from('CMBundle:RelationType', 'rt')
            ->leftJoin('rt.relations', 'r', 'with', 'r.userId = :user_id AND r.fromUserId = :user_from_id')
            ->setParameter('user_from_id', $userFromId)
            ->setParameter('user_id', $userId)
            ->orderBy('rt.id')
            ->getQuery()->getResult();
    }

    public function getRelationTypesPerUser($userId, $accepted, $exclude = false)
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('rt')
            ->from('CMBundle:RelationType', 'rt')
            ->leftJoin('rt.relations', 'r', 'with', 'r.userId = :user_id and r.accepted '.($exclude ? '!=' : '=').' :accepted')
            ->setParameter('user_id', $userId)
            ->setParameter('accepted', $accepted)
            ->orderBy('rt.id')
            ->getQuery()
            ->getResult();
    }
    
    public function getRelations($user1Id, $user2Id, $options = array())
    {
        $options = self::getOptions($options);

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('r')
            ->from($this->_entityName, 'r', is_null($options['indexBy']) ? null : 'r.'.$options['indexBy']);
        $query->andWhere($query->expr()->orX(
                $query->expr()->andX(
                    $query->expr()->eq('r.userId', ':user_1_id'),
                    $query->expr()->eq('r.fromUserId', ':user_2_id')
                ),
                $query->expr()->andX(
                    $query->expr()->eq('r.userId', ':user_2_id'),
                    $query->expr()->eq('r.fromUserId', ':user_1_id')
                )
            ))->setParameter('user_1_id', $user1Id)->setParameter('user_2_id', $user2Id);
        return $query->getQuery()->getResult();
    }

    public function countBy($fields = array(), $options = array())
    {
        $options = self::getOptions($options);

        $query = $this->createQueryBuilder('r')
            ->select('count(r.id)');
        foreach ($fields as $field => $value) {
            $query->andWhere('r.'.$field.' = :'.$field)
                ->setParameter($field, $value);
        }
        return $query->getQuery()->getSingleScalarResult();
    }

    // public function getUserRelations($userId)
    // {
    //     return $this->createQueryBuilder('r')
    //         ->select('r, t, u, fu')
    //         ->leftJoin('r.relationType', 't')
    //         ->leftJoin('r.user', 'u')
    //         ->leftJoin('r.fromUser', 'fu')
    //         ->where('r.fromUserId = :from_user_id')->setParameter('from_user_id', $userId)
    //         ->orWhere('r.userId = :user_id')->setParameter('user_id', $userId)
    //         ->getQuery()->getResult();
    // }

    public function getRelationsPerUser($userId, $accepted = Relation::ACCEPTED_BOTH, $exclude = false, $options = array())
    {
        $options = self::getOptions($options);

        $count = $this->createQueryBuilder('r')
            ->select('count(r.id)')
            ->leftJoin('r.relationType', 'rt')
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted '.($exclude ? '!=' : '=').' :accepted')->setParameter('accepted', $accepted);

        $query = $this->createQueryBuilder('r')
            ->select('r, rt, u')
            ->leftJoin('r.relationType', 'rt')
            ->leftJoin('r.fromUser', 'u')
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted '.($exclude ? '!=' : '=').' :accepted')->setParameter('accepted', $accepted);

        if (!is_null($options['relationTypeId'])) {
            $count->andWhere('rt.id = :relation_type_id')->setParameter('relation_type_id', $options['relationTypeId']);
            $query->andWhere('rt.id = :relation_type_id')->setParameter('relation_type_id', $options['relationTypeId']);
        }

        $query->orderBy('r.createdAt', 'desc')
            ->groupBy('r.relationTypeId');

        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function getRelationsIdsPerUser($userId, $accepted = Relation::ACCEPTED_BOTH, $exclude = false, $options = array())
    {
        $options = self::getOptions($options);

        $query = $this->createQueryBuilder('r')
            ->select('partial r.{id, fromUserId}')
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted '.($exclude ? '!=' : '=').' :accepted')->setParameter('accepted', $accepted);

        if (!is_null($options['relationTypeId'])) {
            $query->leftJoin('r.relationType', 'rt')
                ->andWhere('rt.id = :relation_type_id')->setParameter('relation_type_id', $options['relationTypeId']);
        }

        return $query->orderBy('r.createdAt', 'desc')
            ->getQuery()
            ->getArrayResult();
    }

    public function getSuggestedUsers($userId, $offset, $limit, $rand = false)
    {
        $closest = $this->createQueryBuilder('r')
            ->select('partial r.{id, userId}')
            ->where('r.fromUser = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted = :accepted')->setParameter('accepted', Relation::ACCEPTED_BOTH)
            ->groupBy('r.userId')
            ->getQuery()->getArrayResult();

        if (count($closest) == 0) {
            $randIds = $this->getEntityManager()->getConnection()
                ->executeQuery('SELECT id FROM user WHERE id != '.$userId.' ORDER BY RAND() LIMIT '.$limit)
                ->fetchAll();
            return $this->getEntityManager()->createQueryBuilder()
                ->select('u, uut, ut, utt')
                ->from('CMBundle:User', 'u')
                ->leftJoin('u.userUserTags', 'uut')
                ->leftJoin('uut.userTag', 'ut')
                ->leftJoin('ut.translations', 'utt')
                ->where('u.id in (:ids)')->setParameter('ids', $randIds)
                ->getQuery()
                ->getResult();
        }

        $closest = array_map(function($v) { return $v['userId']; }, $closest);

        $pending = $this->createQueryBuilder('r')
            ->select('partial r.{id, userId}')
            ->where('r.fromUser = :user_id')->setParameter('user_id', $userId)
            ->andWhere('r.accepted = :accepted')->setParameter('accepted', Relation::ACCEPTED_UNI)
            ->groupBy('r.userId')
            ->getQuery()->getArrayResult();

        $pending = array_map(function($v) { return $v['userId']; }, $pending);

        $relations = $this->createQueryBuilder('r')
            ->select('r, u')
            ->leftJoin('r.fromUser', 'u')
            ->andWhere('r.fromUserId in (:user_ids_in)')->setParameter('user_ids_in', $closest)
            ->andWhere('r.userId not in (:user_ids_not_in)')->setParameter('user_ids_not_in', array_merge($closest, array_merge($pending, array($userId))))
            ->andWhere('r.accepted = :accepted')->setParameter('accepted', Relation::ACCEPTED_BOTH);
        if ($rand) {
            $relations->orderBy('rand()');
        }
        $relations = $relations->getQuery()->getResult();

        if (count($relations) == 0) {
            return;
        }

        $suggestions = array();
        if (!$rand) {
            foreach ($relations as $relation) {
                if (!isset($suggestions[$relation->getUserId()])) {
                    $suggestions[$relation->getUserId()]['count'] = 0;
                    // $suggestions[$relation->getUserId()]['userId'] = $relation->getUserId();
                    $suggestions[$relation->getUserId()]['references'] = array();
                }
                $suggestions[$relation->getUserId()]['count']++;
                if (!isset($suggestions[$relation->getUserId()]['references'][$relation->getFromUserId()])) {
                    $suggestions[$relation->getUserId()]['references'][$relation->getFromUserId()] = $relation->getFromUser();
                }
            }
            uasort($suggestions, function($a, $b) { return $a['count'] < $b['count']; });
        } else {
            foreach ($relations as $relation) {
                if (!isset($suggestions[$relation->getUserId()])) {
                    $suggestions[$relation->getUserId()] = $relation->getUserId();
                }
            }
        }
        $suggestions = array_slice($suggestions, $offset, $limit, true);

        $users = $this->getEntityManager()->createQueryBuilder()
            ->select('u, uut, ut, utt')
            ->from('CMBundle:User', 'u')
            ->leftJoin('u.userUserTags', 'uut')
            ->leftJoin('uut.userTag', 'ut')
            ->leftJoin('ut.translations', 'utt')
            ->where('u.id in (:ids)')->setParameter('ids', array_keys($suggestions))
            ->getQuery()
            ->getResult();

        foreach ($users as $user) {
            $suggestions[$user->getId()]['user'] = $user;
        }

        return $suggestions;
    }

    public function getInverse($relationTypeId, $userId, $fromUserId)
    {
        return $this->createQueryBuilder('r')
            ->select('r, t')
            ->leftJoin('r.relationType', 't', 'with', 't.id = :relation_type_id')->setParameter('relation_type_id', $relationTypeId)
            ->andWhere('r.fromUserId = :from_user_id')->setParameter('from_user_id', $userId)
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $fromUserId)
            ->getQuery()->getSingleResult();
    }

    public function update($relationTypeId, $userId, $fromUserId, $accepted)
    {
        return $this->createQueryBuilder('r')
            ->update('CMBundle:Relation', 'r')
            ->andWhere('r.relationType = :relation_type_id')->setParameter('relation_type_id', $relationTypeId)
            ->andWhere('r.fromUserId = :from_user_id')->setParameter('from_user_id', $userId)
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $fromUserId)
            ->set('r.accepted', $accepted)
            ->getQuery()->getSingleResult();
    }

    public function remove($relationTypeId, $userId, $fromUserId)
    {
        $this->createQueryBuilder('r')
            ->delete('CMBundle:Relation', 'r')
            ->where('r.relationTypeId = :relation_type_id')->setParameter('relation_type_id', $relationTypeId)
            ->andWhere('r.fromUserId = :from_user_id')->setParameter('from_user_id', $fromUserId)
            ->andWhere('r.userId = :user_id')->setParameter('user_id', $userId)
            ->getQuery()->execute();
    }
}
