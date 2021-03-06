<?php

namespace CM\CMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class LocaleController extends Controller
{
    /**
     * @Route("/locale/{_locale}", name="user_locale")
     */
    public function localeAction(Request $request, $_locale)
    {
	    $request->getSession()->set('_locale', $_locale);
	    
	    if (!is_null($request->headers->get('referer'))) {
	    	return new RedirectResponse($request->headers->get('referer'));
	    }
	    else {
		    return new RedirectResponse($this->generateUrl('event_index'));
	    }
    }
}
