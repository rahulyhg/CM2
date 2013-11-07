<?php

namespace CM\CMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request as sfRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Doctrine\Common\Collections\ArrayCollection;
use CM\CMBundle\Entity\Event;
use CM\CMBundle\Entity\User;
use CM\CMBundle\Entity\EntityUser;
use CM\CMBundle\Entity\Request;
use CM\CMBundle\Entity\Notification;
use CM\CMBundle\Form\EventType;

class UserController extends Controller
{
    /**
     * @Route("/typeaheadHint", name="user_typeahead_hint")
     */
    public function typeaheadHintAction(sfRequest $request)
    {
        $em = $this->getDoctrine()->getManager();

        $exclude = $request->query->get('exclude') ? explode(',', $request->query->get('exclude')) : array();
        $exclude[] = $this->getUser()->getId();
        $users = $em->getRepository('CMBundle:User')->getFromAutocomplete($request->query->get('query'), $exclude);

        $results = array();
        foreach($users as $user)
        {
            if ($user['img'] == '' || $user['img'] == null) {
                $user['img'] = '/uploads/utenti/avatar/50/default.jpg';
            } else {
                $user['img'] = '/uploads/utenti/avatar/50/'.$user['img'];
            }
            $user['fullname'] = $user['firstName'].' '.$user['lastName'];
            $results[] = $user;
        }

        return new JsonResponse($results);
    }

    /**
     * @Route("/menu", name="user_menu")
     * @Template
     */
    public function menuAction(sfRequest $request)
    {
        $em = $this->getDoctrine()->getManager();

        $newRequests = $em->getRepository('CMBundle:User')->getNumberNewRequests($this->getUser()->getId());
        $newNotifications = $em->getRepository('CMBundle:User')->getNumberNewNotifications($this->getUser()->getId());

        $inRequest = substr($request->get('realRoute'), 1, 7) == 'request';
        $inNotification = substr($request->get('realRoute'), 1, 12) == 'notification';

        return array(
            'newRequests' => $newRequests,
            'newNotifications' => $newNotifications,
            'inRequestPage' => $inRequest,
            'inNotificationPage' => $inNotification
        );
    }

    /**
     * @Route("/requests/{page}/{perPage}", name="user_requests", requirements={"page" = "\d+"})
     * @Template
     */
    public function requestsAction(sfRequest $request, $page = 1, $perPage = 6)
    {
        $em = $this->getDoctrine()->getManager();

        $requests = $em->getRepository('CMBundle:User')->getRequests($this->getUser()->getId());
        $pagination = $this->get('knp_paginator')->paginate($requests, $page, $perPage);

        if ($request->isXmlHttpRequest() && !$request->get('outgoing')) {
            return $this->render('CMBundle:User:requestList.html.twig', array('requests' => $pagination));
        }

        $requestsOutgoing = $em->getRepository('CMBundle:User')->getRequests($this->getUser()->getId(), 'outgoing');
        $paginationOutgoing = $this->get('knp_paginator')->paginate($requestsOutgoing, $page, $perPage);

        return array('requests' => $pagination, 'requestsOutgoing' => $paginationOutgoing);
    }

    /**
     * @Route("/requestsSeen", name="user_requests_seen")
     */
    public function requestsSeen(sfRequest $request)
    {
        $em = $this->getDoctrine()->getManager();

        $requestIds = explode(',', $request->get('ids'));

        $newRequests = $this->get('cm.request_center')->seeRequests($this->getUser()->getId(), $requestIds);

        return new JsonResponse(array('new' => $newRequests));
    }

    /**
     * @Route("/requestUpdate/{id}/{choice}", name="user_request_update", requirements={"id"="\d+", "choice"="accept|refuse"})
     */
    public function requestUpdateAction($id, $choice)
    {
        $em = $this->getDoctrine()->getManager();

        if ($choice == 'accept') {
            $this->get('cm.request_center')->acceptRequest($userId, $id);
        } elseif ($choice == 'refuse') {
            $this->get('cm.request_center')->refuseRequest($userId, $id);
        }

        // echo $choice.' '.$newStatus;die;

        $em->getRepository('CMBundle:User')->updateRequestsStatus($this->getUser()->getId(), $id, null, $newStatus);

        return new Response;
    }
    
    /**
     * @Route("/requestDelete/{id}", name="user_request_delete", requirements={"id"="\d+"})
     */
    public function requestDeleteAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $em->getRepository('CMBundle:User')->deleteRequest($this->getUser()->getId(), $id);

        return new Response;
    }

    /**
     * @Route("/notifications/{page}/{perPage}", name="user_notifications", requirements={"page" = "\d+"})
     * @Template
     */
    public function notificationsAction(sfRequest $request, $page = 1, $perPage = 6)
    {
        $em = $this->getDoctrine()->getManager();

        $notifications = $em->getRepository('CMBundle:User')->getNotifications($this->getUser()->getId());
        $pagination = $this->get('knp_paginator')->paginate($notifications, $page, $perPage);

        if ($request->isXmlHttpRequest()) {
            return $this->render('CMBundle:User:notificationList.html.twig', array('notifications' => $pagination));
        }

        return array('notifications' => $pagination);
    }

    /**
     * @Route("/notificationsSeen", name="user_notifications_seen")
     */
    public function notificationsSeen(sfRequest $request)
    {
        $em = $this->getDoctrine()->getManager();

        $notificationIds = explode(',', $request->get('ids'));

        $em->getRepository('CMBundle:User')->updateNotificationsStatus($this->getUser()->getId(), $notificationIds, Notification::STATUS_NEW);

        $newNotifications = $em->getRepository('CMBundle:User')->getNumberNewNotifications($this->getUser()->getId());

        return new JsonResponse(array('new' => $newNotifications));
    }

    /**
     * @Route("/{slug}", name="user_show")
     * @Template
     */
    public function showAction($slug)
    {
        return array('username' => $slug);
    }
    
    /**
	 * @Route("/{slug}/events/{page}", name="user_events", requirements={"page" = "\d+"})
	 * @Route("/{slug}/events/archive/{page}", name="user_events_archive", requirements={"page" = "\d+"}) 
	 * @Route("/{slug}/events/category/{category_slug}/{page}", name="user_events_category", requirements={"page" = "\d+"})
     * @Template
     */
	public function eventsAction(sfRequest $request, $slug, $page = 1, $category_slug = null)
	{
	    $em = $this->getDoctrine()->getManager();
		
		$user = $em->getRepository('CMBundle:User')->findOneBy(array('usernameCanonical' => $slug));
		
		if (!$user) {
    		throw new NotFoundHttpException('User not found.');
		}
		    
		if (!$request->isXmlHttpRequest()) {
			$categories = $em->getRepository('CMBundle:EntityCategory')->getEntityCategories(EntityCategory::EVENT, array('locale' => $request->getLocale()));
		}
		
		if ($category_slug) {
			$category = $em->getRepository('CMBundle:EntityCategory')->getCategory($category_slug, EntityCategory::EVENT, array('locale' => $request->getLocale()));
		}
			
		$events = $em->getRepository('CMBundle:Event')->getEvents(array(
			'locale'        => $request->getLocale(), 
			'archive'       => $request->get('_route') == 'event_archive' ? true : null,
			'category_id'   => $category_slug ? $category->getId() : null,
			'user_id'       => $user->getId()		
        ));
        
		$pagination = $this->get('knp_paginator')->paginate($events, $page, 10);
		
		if ($request->isXmlHttpRequest()) {
    		return $this->render('CMBundle:Event:objects.html.twig', array('dates' => $pagination, 'page' => $page));
		}
		
		return array('categories' => $categories, 'user' => $user, 'dates' => $pagination, 'category' => $category, 'page' => $page);
	}
}
