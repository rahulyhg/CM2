<?php

namespace CM\CMBundle\Service;

use Doctrine\ORM\EntityManager;
use CM\CMBundle\Entity\Request;
use CM\CMBundle\Entity\EntityUser;

class RequestCenter
{
    private $em;

    private $flushNeeded = false;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;
    }

    public function flushNeeded()
    {
        if ($this->flushNeeded) {
            $this->flushNeeded = false;
            return true;
        }

        return false;
    }

    public function newRequest(
        $toUser,
        $fromUser,
        $object,
        $objectId,
        $entity = null,
        $page = null,
        $group = null
    )
    {
        if ($toUser->getId() == $fromUser->getId()) {
            return;
        }
        $request = new Request;
        $request->setUser($toUser)
            ->setFromUser($fromUser);
        if (!is_null($entity)) {
            $request->setEntity($entity);
        } else {
            $request->setObject($object)
                ->setObjectId($objectId);
        }
        if (!is_null($page)) {
            $request->setFromPage($page);
        } elseif (!is_null($group)) {
            $request->setFromGroup($group);
        }
        $this->em->persist($request);
        $this->flushNeeded = true;
    }

    public function removeRequests($toUser, $object, $objectId)
    {
        $requests = $this->em->getRepository('CMBundle:Request')->getFor($toUser, $object, $objectId);
        foreach ($requests as $request) {
            $this->em->remove($request);
            $this->flushNeeded = true;
        }
    }
}