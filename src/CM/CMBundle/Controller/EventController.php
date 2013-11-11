<?php

namespace CM\CMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use JMS\SecurityExtraBundle\Annotation as JMS;
use Doctrine\Common\Collections\ArrayCollection;
use Knp\DoctrineBehaviors\ORM\Translatable\CurrentLocaleCallable;
use CM\CMBundle\Entity\Post;
use CM\CMBundle\Entity\EntityCategory;
use CM\CMBundle\Entity\Event;
use CM\CMBundle\Entity\EntityUser;
use CM\CMBundle\Entity\EventDate;
use CM\CMBundle\Entity\Image;
use CM\CMBundle\Entity\Sponsored;
use CM\CMBundle\Form\EventType;
use CM\CMBundle\Form\ImageCollectionType;
use CM\CMBundle\Utility\UploadHandler;

/**
 * @Route("/events")
 */
class EventController extends Controller
{
	/**
	 * @Route("/{page}", name = "event_index", requirements={"page" = "\d+"})
	 * @Route("/archive/{page}", name="event_archive", requirements={"page" = "\d+"}) 
	 * @Route("/category/{category_slug}/{page}", name="event_category", requirements={"page" = "\d+"})
	 * @Template
	 */
	public function indexAction(Request $request, $page = 1, $category_slug = null)
	{
	    $em = $this->getDoctrine()->getManager();
		    
		if (!$request->isXmlHttpRequest()) {
			$categories = $em->getRepository('CMBundle:EntityCategory')->getEntityCategories(EntityCategory::EVENT, array('locale' => $request->getLocale()));
		}
		
		if ($category_slug) {
			$category = $em->getRepository('CMBundle:EntityCategory')->getCategory($category_slug, EntityCategory::EVENT, array('locale' => $request->getLocale()));
		}
			
		$events = $em->getRepository('CMBundle:Event')->getEvents(array(
			'locale'        => $request->getLocale(), 
			'archive'       => $request->get('_route') == 'event_archive' ? true : null,
			'category_id'   => $category_slug ? $category->getId() : null
        ));
        
		$pagination = $this->get('knp_paginator')->paginate($events, $page, 10);
		
		if ($request->isXmlHttpRequest()) {
    		return $this->render('CMBundle:Event:objects.html.twig', array('dates' => $pagination, 'page' => $page));
		}
		
		return array('categories' => $categories, 'dates' => $pagination, 'category' => $category, 'page' => $page);
	}
	
	/**
	 * @Route("/calendar/{year}/{month}", name = "event_calendar", requirements={"month" = "\d+", "year" = "\d+"})
	 * @Template
	 */
	public function calendarAction(Request $request, $year = null, $month = null)
	{
			
		if ($request->isXmlHttpRequest()) {
			return $this->forward('CMBundle:Event:calendarMonth', array('request' => $request, 'month' => $month, 'year' => $year));
		}
		
        $month = !is_null($month) ? $month : date('n');
        $year = !is_null($year) ? $year : date('Y');
        
		$em = $this->getDoctrine()->getManager();
		$categories = $em->getRepository('CMBundle:EntityCategory')->getEntityCategories(EntityCategory::EVENT, array('locale' => $request->getLocale()));
			
		return array('categories' => $categories, 'year' => $year, 'month' => $month);
	}
	
	/**
	 * @Template
	 */
	public function calendarMonthAction(Request $request, $year, $month)
	{
	    $request->setLocale($request->get('_locale')); // TODO: workaround for locale in subsession
	    
		$em = $this->getDoctrine()->getManager();
		
		$dates = $em->getRepository('CMBundle:Event')->getEventsPerMonth($year, $month, array('locale' => $request->getLocale()));
		
        $cMonth['weekdayNames'] = array('sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'); 
        $cMonth['cMonth'] = !is_null($month) ? $month : date('n');
        $cMonth['cYear'] = !is_null($year) ? $year : date('Y');
	 
        $cMonth['prev_year'] = $cMonth['cYear'];
    	$cMonth['next_year'] = $cMonth['cYear'];
    	$cMonth['prev_month'] = $cMonth['cMonth'] - 1;
    	$cMonth['next_month'] = $cMonth['cMonth'] + 1;
    	 
    	if ($cMonth['prev_month'] == 0) {
            $cMonth['prev_month'] = 12;
            $cMonth['prev_year'] = $cMonth['cYear'] - 1;
    	}
    	if ($cMonth['next_month'] == 13) {
    	    $cMonth['next_month'] = 1;
            $cMonth['next_year'] = $cMonth['cYear'] + 1;
    	}
    				
    	$cMonth['timestamp'] = mktime(0, 0, 0, $cMonth['cMonth'], 1, $cMonth['cYear']);
    	$cMonth['maxday'] = date('t', strtotime($year.'-'.$month.'-01'));
    	$cMonth['thismonth'] = getdate($cMonth['timestamp']);
    	$cMonth['startday'] = $cMonth['thismonth']['wday'];
/*
	    $cal1 = \IntlCalendar::createInstance();
        echo $cal1->getFirstDayOfWeek(); // Monday
*/
/*     	$cMonth['calendar'] = \IntlCalendar::createInstance(); */
			
		if ($request->isXmlHttpRequest()) {
			return $this->render('CMBundle:Event:calendarMonth.html.twig', array('dates' => $dates, 'month' => $cMonth));
		}

		return array('dates' => $dates, 'month' => $cMonth);
	}
    
    /**
     * @Route("/{id}/{slug}", name="event_show", requirements={"id" = "\d+", "_locale" = "en|fr|it"})
     * @Template
     */
    public function showAction(Request $request, $id, $slug)
    {
        $em = $this->getDoctrine()->getManager();
        	
		if ($request->isXmlHttpRequest()) {
            $date = $em->getRepository('CMBundle:Event')->getDate($id, array('locale' => $request->getLocale()));
			return $this->render('CMBundle:Event:object.html.twig', array('date' => $date));
		}
		
        $event = $em->getRepository('CMBundle:Event')->getEvent($id, array('locale' => $request->getLocale(), 'protagonists' => true));
        $tags = $em->getRepository('CMBundle:UserTag')->getUserTags(array('locale' => $request->getLocale()));

        $images = new ArrayCollection();

        $form = $this->createForm(new ImageCollectionType(), $images, array(
                'action' => $this->generateUrl('event_show', array(
                'id' => $event->getId(),
                'slug' => $event->getSlug()
            )),
            'cascade_validation' => true
        ))->add('save', 'submit');

        $form->handleRequest($request);
        
        if ($form->isValid()) {
            $event->addImages($images);

            $em = $this->getDoctrine()->getEntityManager();
            $em->persist($event);
            $em->flush();

            return new RedirectResponse($this->generateUrl('event_show', array('id' => $event->getId(), 'slug' => $event->getSlug())));
        }
        
        return array('event' => $event, 'tags' => $tags, 'form' => $form->createView());
    }
    
    /**
     * @Route("/new", name="event_new") 
     * @Route("/{id}/{slug}/edit", name="event_edit", requirements={"id" = "\d+"})
     * @JMS\Secure(roles="ROLE_USER")
     * @Template
     */
    public function editAction(Request $request, $id = null, $slug = null)
    {
        if (!$this->get('cm.user_authentication')->isAuthenticated()) {
            return new RedirectResponse($this->generateUrl('fos_user_security_login'));
        }

        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        
        if ($id == null || $slug == null) {
            
            $event = new Event;

            $event->addUser(
                $user,
                true, // admin
                EntityUser::STATUS_ACTIVE,
                true // notifications
            );

            $image = new Image;
            $image->setMain(true)
                ->setUser($user);
            $event->addImage($image);

            $event->addEventDate(new EventDate);

            $post = $this->get('cm.post_center')->getNewPost($user, $user);

            $event->addPost($post);
        } else {
            $event = $em->getRepository('CMBundle:Event')->getEvent($id, array('locale' => $request->getLocale(), 'protagonists' => true));
            // TODO: retrieve images from event
        }

        $oldEntityUsers = array();
        foreach ($event->getEntityUsers() as $oldEntityUser) {
            $oldEntityUsers[] = $oldEntityUser;
        }

        $oldEventDates = array();
        foreach ($event->getEventDates() as $oldEventDate) {
            $oldEventDates[] = $oldEventDate;
        }
        
        // TODO: retrieve locales from user

        if ($request->get('_route') == 'event_edit') {
            $formRoute = 'event_edit';
            $formRouteArgs = array('id' => $event->getId(), 'slug' => $event->getSlug());
        } else {
            $formRoute = 'event_new';
            $formRouteArgs = array();
        }
 
        $form = $this->createForm(new EventType, $event, array(
/*             'action' => $this->generateUrl($formRoute, $formRouteArgs), */
            'cascade_validation' => true,
            'error_bubbling' => false,
            'em' => $em,
            'roles' => $user->getRoles(),
            'user_tags' => $em->getRepository('CMBundle:UserTag')->getUserTags(array('locale' => $request->getLocale())),
            'locales' => array('en'/* , 'fr', 'it' */),
            'locale' => $request->getLocale()
        ))->add('save', 'submit');
        
        $form->handleRequest($request);

        if (!$this->get('cm.user_authentication')->canManage($event)) {
              throw new HttpException(401, 'Unauthorized access.');
        } elseif ($form->isValid()) {
            foreach ($event->getEventDates() as $eventDate) {
                foreach ($oldEventDates as $key => $toDel) {
                    if ($toDel->getId() === $eventDate->getId()) {
                        unset($oldEventDates[$key]);
                    }
                }
            }
    
            // remove the relationship between the tag and the Task
            foreach ($oldEventDates as $eventDate) {
                // remove the Task from the Tag
                $event->removeEventDate($eventDate);
    
                // if it were a ManyToOne relationship, remove the relationship like this
                // $tag->setTask(null);
    
                // if you wanted to delete the Tag entirely, you can also do that
                $em->remove($eventDate);
            }

            foreach ($event->getEntityUsers() as $entityUser) {
                foreach ($oldEntityUsers as $key => $toDel) {
                    if ($toDel->getId() === $entityUser->getId()) {
                        unset($oldEntityUsers[$key]);
                    }
                }
            }

            // remove the relationship between the tag and the Task
            foreach ($oldEntityUsers as $entityUser) {
                // remove the Task from the Tag
                $event->removeEntityUser($entityUser);

                $entityUser->setEntity(null);
                $entityUser->setUser(null);
    
                $em->remove($entityUser);
            }

            $em->persist($event);

            $em->flush();

            // foreach ($event->getEntityUsers() as $entityUser) {
            //     echo $entityUser->getUser().' -> i: '.count($entityUser->getUser()->getRequestsIncoming()).', o: '.'<br/>';
            // }
            // die;
                  
            return new RedirectResponse($this->generateUrl('event_show', array('id' => $event->getId(), 'slug' => $event->getSlug())));
        }

        $users = array();
        foreach ($event->getEntityUsers() as $entityUser) {
            $users[] = $entityUser->getUser();
        }
        
        return array(
            'form' => $form->createView(),
            'entity' => $event,
            'newEntry' => ($formRoute == 'event_new'),
            'joinEntityType' => 'joinEvent'
        );
    }

    /**
     * @Route("/eventDelete/{id}", name="event_delete", requirements={"id" = "\d+"})
     * @JMS\Secure(roles="ROLE_USER")
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $event = $em->getRepository('CMBundle:Event')->findOneById($id);

        if (!$this->get('cm.user_authentication')->canManage($event)) {
              throw new HttpException(401, 'Unauthorized access.');
        }

        $em->remove($event);
        $em->flush();

        return new JsonResponse(array('title' => $event->getTitle()));
    }
    
    /**
     * @Template
     */
    public function sponsoredAction(Request $request, $limit = 3)
    {
	    $request->setLocale($request->get('_locale')); // TODO: workaround for locale in subsession
		
		$sponsored = $this->getDoctrine()->getManager()->getRepository('CMBundle:Event')->getSponsored(array('limit' => $limit, 'locale' => $request->getLocale()));
        
		$pagination  = $this->get('knp_paginator')->paginate($sponsored, 1, $limit);
		
		$this->getDoctrine()->getManager()->createQuery("UPDATE CMBundle:Sponsored s SET s.views = s.views + 1 WHERE s.id IN (2, 20)")->getResult();
        
        return array('sponsored_events' => $pagination);
    }
}
