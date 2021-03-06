<?php

namespace CM\CMBundle\Entity;

use Doctrine\ORM\EntityRepository as BaseRepository;

/**
 * ImageAlbumRepository
 *
 * This class was generated ay the Doctrine ORM. Add your own custom
 * repository methods aelow.
 */
class ImageAlbumRepository extends BaseRepository
{
    static protected function getOptions(array $options = array())
    {
        $options = array_merge(array(
            'locale'        => 'en'
        ), $options);
        
        return array_merge(array(
            'slug' => null,
            'userId'       => null,
            'pageId'       => null,
            'type'         => ImageAlbum::TYPE_ALBUM,
            'entities' => false,
            'entityType' => null,
            'locales'       => array_values(array_merge(array('en' => 'en'), array($options['locale'] => $options['locale']))),
            'after' => null,
            'paginate'      => true,
            'limit'         => 25,
        ), $options);
    }

    public function countAlbumsAndImages($options = array())
    {
        $options = self::getOptions($options);

        $albums = $this->createQueryBuilder('a')
            ->select('count(a.id)')
            ->join('a.post', 'p')
            ->where('a instance of :image_album')->setParameter('image_album', 'image_album');
        $entities = $this->getEntityManager()->createQueryBuilder()
            ->select('count(e.id)')
            ->from('CMBundle:Entity', 'e')
            ->join('e.post', 'p')
            ->where('e not instance of :image_album')->setParameter('image_album', 'image_album')
            ->andWhere('size(e.images) > 1');
        $images = $this->getEntityManager()->createQueryBuilder()
            ->select('count(i.id)')
            ->from('CMBundle:Image', 'i')
            ->leftJoin('i.entity', 'e')
                ->andWhere($albums->expr()->orX(
                'i.entityId is null',
                'e instance of :image_album'
            ))->setParameter('image_album', 'image_album');
        if (!is_null($options['userId'])) {
            $albums->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
            $entities->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
            $images->andWhere('i.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('i.pageId is NULL');
        }
        if (!is_null($options['pageId'])) {
            $albums->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
            $entities->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
            $images->andWhere('i.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }

        return array(
            'albums' => $albums->getQuery()->getSingleScalarResult(),
            'entities' => $entities->getQuery()->getSingleScalarResult(),
            'images' => $images->getQuery()->getSingleScalarResult()
        );
    }

    public function getImageAlbum($options = array())
    {
        $options = self::getOptions($options);

        $query = $this->createQueryBuilder('a')
            ->select('a')
            ->leftJoin('a.posts', 'p');
        if (!is_null($options['userId'])) {
            $query->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
        }
        if (!is_null($options['pageId'])) {
            $query->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        $query->andWhere('a.type = :type')->setParameter('type', $options['type']);
        $album = $query->setMaxResults(1)->getQuery()->getResult();
        if (is_array($album) && count($album) > 0) {
            $album = $album[0];
        } else {
            $album = null;
        }
        return $album;
    }

    public function getImagesDataInAlbum($id, $data = 'id')
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('partial i.{'.$data.'}')
            ->from('CMBundle:Image', 'i')
            ->where('i.entityId = :album_id')->setParameter('album_id', $id)
            ->orderBy('i.sequence')
            ->addOrderBy('i.id', 'desc')
            ->getQuery()->getArrayResult();
    }

    public function getImagesDataPerPublisher($type, $id, $data = 'id')
    {
        return $this->getEntityManager()->createQueryBuilder()
            ->select('partial i.{'.$data.'}')
            ->from('CMBundle:Image', 'i')
            ->where('i.'.$type.'Id = :type_id')->setParameter('type_id', $id)
            ->addOrderBy('i.id', 'desc')
            ->getQuery()->getArrayResult();
    }

    public function getLastPost($id, $options = array())
    {
        $options = self::getOptions($options);

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('p')
            ->from('CMBundle:Post', 'p')
            ->leftJoin('p.entity', 'e')
            ->leftJoin('p.entity', 'a', 'with', 'a instance of :image_album')->setParameter('image_album', 'image_album')
            ->where('a.id = :id')->setParameter('id', $id);
        if (!is_null($options['userId'])) {
            $query->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId']);
        }
        if (!is_null($options['pageId'])) {
            $query->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        if (!is_null($options['after'])) {
            $query->andWhere('p.updatedAt > :time')->setParameter('time', $options['after']);
        }
        $query->andWhere('a.type = :type')->setParameter('type', $options['type'])
            ->orderBy('p.id', 'desc');
        $post = $query->setMaxResults(1)->getQuery()->getResult();
        if (is_array($post) && count($post) > 0) {
            $post = $post[0];
        } else {
            $post = null;
        }
        return $post;
    }

    public function getAlbums($options = array())
    {
        $options = self::getOptions($options);

        $count = $this->createQueryBuilder('a')
            ->select('count(a.id)');
        if (!is_null($options['userId'])) {
            $count->join('a.post', 'p')
                ->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
        }
        if (!is_null($options['pageId'])) {
            $count->join('a.post', 'p')
                ->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }

        $query = $this->createQueryBuilder('a')
            ->select('a')
            ->join('a.images', 'i');
        if (!is_null($options['userId'])) {
            $query->join('a.post', 'p')
                ->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
        }
        if (!is_null($options['pageId'])) {
            $query->join('a.post', 'p')
                ->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        $query->orderBy('a.type')
            ->addOrderBy('i.id', 'desc');

        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function getEntities($options = array())
    {
        $options = self::getOptions($options);

        $count = $this->getEntityManager()->createQueryBuilder()
            ->select('count(e.id)')
            ->from('CMBundle:Entity', 'e');
        if (!is_null($options['userId'])) {
            $count->join('e.entityUsers', 'eu')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
        }
        if (!is_null($options['pageId'])) {
            $count->join('e.post', 'p')
                ->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        $count->andWhere('e not instance of :image_album')->setParameter('image_album', 'image_album')
            ->andWhere('size(e.images) > 1');

        $query = $this->getEntityManager()->createQueryBuilder()
            ->select('e')
            ->from('CMBundle:Entity', 'e')
            ->join('e.images', 'i');
        if (!is_null($options['userId'])) {
            $query->join('e.entityUsers', 'eu')
                ->andWhere('eu.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('eu.status = '.EntityUser::STATUS_ACTIVE);
        }
        if (!is_null($options['pageId'])) {
            $query->join('e.post', 'p')
                ->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }
        $query->andWhere('e not instance of :image_album')->setParameter('image_album', 'image_album')
            ->andWhere('size(e.images) > 1')
            ->orderBy('e.id', 'desc')
            ->addOrderBy('i.id', 'desc');

        return $options['paginate'] ? $query->getQuery()->setHint('knp_paginator.count', $count->getQuery()->getSingleScalarResult()) : $query->setMaxResults($options['limit'])->getQuery()->getResult();
    }

    public function getAlbum($id, $options = array())
    {
        $options = self::getOptions($options);

        $query = $this->getEntityManager()->createQueryBuilder('e')
            ->select('e, t')->from('CMBundle:'.(!is_null($options['entityType']) ? ucfirst(substr($options['entityType'], 0, -1)) : 'Entity'), 'e')
            ->leftJoin('e.translations', 't', 'with', 't.locale IN (:locales)')
            ->setParameter('locales', $options['locales'])
            ->andWhere('e.id = :id')->setParameter('id', $id);
        if (!is_null($options['slug'])) {
            $query->andWhere('t.slug = :slug')->setParameter('slug', $options['slug']);
        }
        if (!is_null($options['userId'])) {
            $query->join('e.post', 'p')
                ->andWhere('p.userId = :user_id')->setParameter('user_id', $options['userId'])
                ->andWhere('p.pageId is NULL');
        }
        if (!is_null($options['pageId'])) {
            $query->join('e.post', 'p')
                ->andWhere('p.pageId = :page_id')->setParameter('page_id', $options['pageId']);
        }

        return $query->getQuery()->getSingleResult();
    }

    public function setMain($id, $albumId)
    {
        $this->getEntityManager()->createQueryBuilder('i')
            ->update('CMBundle:Image', 'i')
            ->set('i.main', 0)
            ->where('i.entityId = :entity_id')->setParameter('entity_id', $albumId)
            ->getQuery()->execute();

        $this->getEntityManager()->createQueryBuilder('i')
            ->update('CMBundle:Image', 'i')
            ->set('i.main', 1)
            ->where('i.id = :id')->setParameter('id', $id)
            ->getQuery()->execute();
    }
}
