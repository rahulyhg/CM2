<?php

namespace CM\CMBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\Process\Exception\RuntimeException;
use JMS\SecurityExtraBundle\Annotation as JMS;
use CM\CMBundle\Entity\Multimedia;
use CM\CMBundle\Entity\EntityUser;
use CM\CMBundle\Entity\Post;
use CM\CMBundle\Form\MultimediaType;

/**
 * @Route("/multimedia")
 */
class MultimediaController extends Controller
{
    /**
     * @Route("/{page}", name="multimedia_index", requirements={"page" = "\d+"})
     * @Template
     */
    public function indexAction(Request $request, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        
        $multimedias = $em->getRepository('CMBundle:Multimedia')->getMultimedias();
        $pagination = $this->get('knp_paginator')->paginate($multimedias, $page, 10);

        if ($request->isXmlHttpRequest()) {
            return $this->render('CMBundle:Multimedia:objects.html.twig', array(
                'multimedias' => $pagination
            ));
        }

        return array(
            'multimedias' => $pagination
        );
    }

    /**
     * @Route("/all/{entityId}/{page}", name="multimedia_show_all", requirements={"page" = "\d+"})
     * @Template
     */
    public function showAllAction(Request $request, $entityId, $page = 1)
    {
        $em = $this->getDoctrine()->getManager();
        
        $multimedias = $em->getRepository('CMBundle:Multimedia')->getMultimedias(array('entityId' => $entityId));
        $pagination = $this->get('knp_paginator')->paginate($multimedias, $page, 10);

        return array(
            'multimedias' => $pagination
        );
    }
    
    /**
     * @Route("/entity/{type}/{id}", name="multimedia_entity", requirements={"id" = "\d+"}) 
     * @Template
     */
    public function entityAction(Request $request, $id, $type)
    {
        return array(
            'entityId' => $id,
            'entityType' => $type,
            'count' => $this->getDoctrine()->getManager()->getRepository('CMBundle:Multimedia')->countBy(array('entityId' => $id)),
            'multimedias' => $this->getDoctrine()->getManager()->getRepository('CMBundle:Multimedia')->findBy(array('entityId' => $id), array(), 3)
        );
    }

    /**
     * @Route("/new/{object}/{objectId}", name="multimedia_new", requirements={"objectId" = "\d+"})
     * @Route("/{id}/{slug}/edit", name="multimedia_edit", requirements={"id" = "\d+"})
     * @JMS\Secure(roles="ROLE_USER")
     * @Template
     */
    public function editAction(Request $request, $object = null, $objectId = null, $id = null, $slug = null)
    {
        $em = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        $page = null;
        if (!is_null($objectId)) {
            $page = $em->getRepository('CMBundle:Page')->findOneById($objectId);

            if (!$this->get('cm.user_authentication')->isAdminOf($page)) {
                throw new HttpException(403, $this->get('translator')->trans('You cannot do this.', array(), 'http-errors'));
            }
            if (is_null($page)) {
                throw new NotFoundHttpException($this->get('translator')->trans('Object not found.', array(), 'http-errors'));
            }
        }
        
        if (is_null($id)) {
            $multimedia = new Multimedia;

            $post = $this->get('cm.post_center')->newPost(
                $user,
                $user,
                Post::TYPE_CREATION,
                get_class($multimedia),
                array(),
                $multimedia,
                $page
            );

            $multimedia->addPost($post);
        } else {
            $multimedia = $em->getRepository('CMBundle:Multimedia')->getMultimedia($id, array('locale' => $request->getLocale()));
            if (!$this->get('cm.user_authentication')->canManage($multimedia)) {
                throw new HttpException(403, $this->get('translator')->trans('You cannot do this.', array(), 'http-errors'));
            }
        }
 
        $form = $this->createForm(new MultimediaType, $multimedia, array(
            'cascade_validation' => true,
            'error_bubbling' => false,
            'em' => $em,
            'roles' => $user->getRoles()
        ))->add('save', 'submit');
        
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em->persist($multimedia);
            $em->flush();

            return new RedirectResponse($this->generateUrl($multimedia->getPost()->getPublisherType().'_multimedia_show', array('id' => $multimedia->getId(), 'slug' => $multimedia->getPost()->getPublisher()->getSlug())));
        }

        $users = array();
        foreach ($multimedia->getEntityUsers() as $entityUser) {
            $users[] = $entityUser->getUser();
        }
        
        return array(
            'form' => $form->createView(),
            'entity' => $multimedia,
            'joinEntityType' => 'joinalbum'
        );
    }

    /**
     * @Route("/multimedia/{id}/add", name="multimedia_add_multimedia", requirements={"id" = "\d+"})
     * @JMS\Secure(roles="ROLE_USER")
     * @Template("CMBundle:MultimediaAlbum:singleMultimedia.html.twig")
     */
    public function addMultimediaAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $album = $em->getRepository('CMBundle:MultimediaAlbum')->getAlbum($id, array('locale' => $request->getLocale()));

        if (!$this->get('cm.user_authentication')->canManage($album)) {
            throw new HttpException(403, $this->get('translator')->trans('You cannot do this.', array(), 'http-errors'));
        }

        $multimedia = new Multimedia;
        $multimedia->setType($type);
        $multimedia->setLink($link);
        $multimedia->setTitle($info->title)
            ->setText($info->description);;
        if (!is_null($album->getPost()->getPage())) {
            $multimedia->setPage($album->getPost()->getPage());
            $publisher = $album->getPost()->getPage();
            $link = 'page_multimedia';
        } else {
            $publisher = $this->getUser();
            $link = 'user_multimedia';
        }

        foreach ($request->files as $file) {
            $multimedia->setImgFile($file);
        }

        $errors = $this->get('validator')->validate($multimedia);

        if (count($errors) > 0) {
            throw new HttpException(403, $this->get('translator')->trans('Error in file.', array(), 'http-errors'));
        }

        $em->persist($album);
        $em->flush();

        return array(
            'multimedia' => $multimedia,
            'link' => $link,
            'publisher' => $publisher
        );
    }
    
    /**
     * @Route("/{id}/{slug}", name="multimedia_show", requirements={"id" = "\d+"})
     * @Template
     */
    public function showAction(Request $request, $id, $slug)
    {
        $em = $this->getDoctrine()->getManager();
            
        // if ($request->isXmlHttpRequest()) {
        //     $date = $em->getRepository('CMBundle:Multimedia')->getDate($id, array('locale' => $request->getLocale()));
        //     return $this->render('CMBundle:Multimedia:object.html.twig', array('date' => $date));
        // }
        
        $multimedia = $em->getRepository('CMBundle:Multimedia')->getMultimedia($id, array('locale' => $request->getLocale()));
        $tags = $em->getRepository('CMBundle:UserTag')->getUserTags(array('locale' => $request->getLocale()));
        
        if ($request->isXmlHttpRequest()) {
            return $this->render('CMBundle:Multimedia:object.html.twig', array('multimedia' => $multimedia));
        }
        
        return array('multimedia' => $multimedia);
    }
}