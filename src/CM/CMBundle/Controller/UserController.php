<?php

namespace CM\CMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use CM\CMBundle\Entity\EntityCategory;

/**
 * @Route("/{username}", defaults={"username": null})
 */
class UserController extends Controller
{
    /**
     * @Route("/", name="user_show")
     * @Template
     */
    public function showAction($username)
    {
        return array('username' => $username);
    }
    
    /**
	 * @Route("/events/{page}", name = "user_events", requirements={"page" = "\d+"})
	 * @Route("/events/archive/{page}", name="user_events_archive", requirements={"page" = "\d+"}) 
	 * @Route("/events/category/{category_slug}/{page}", name="user_events_category", requirements={"page" = "\d+"})
     * @Template
     */
	public function eventsAction(Request $request, $username, $page = 1, $category_slug = null, $user_id = null)
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
			'category_id'   => $category_slug ? $category->getId() : null,
			'user_id'       => $user_id		
        ));
        
		$pagination = $this->get('knp_paginator')->paginate($events, $page, 10);
		
		if ($request->isXmlHttpRequest()) {
    		return $this->render('CMBundle:Event:objects.html.twig', array('dates' => $pagination, 'page' => $page));
		}
		
		$user = $em->getRepository('CMBundle:User')->findOneBy(array('usernameCanonical' => $username));
		
		return array('categories' => $categories, 'user' => $user, 'dates' => $pagination, 'category' => $category, 'page' => $page);
	}
}
