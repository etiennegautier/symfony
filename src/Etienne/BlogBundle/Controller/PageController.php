<?php

namespace Etienne\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Etienne\BlogBundle\Entity\Enquiry;
use Etienne\BlogBundle\Form\EnquiryType;
use Guzzle\Service\Description\ServiceDescription;

class PageController extends Controller
{
    public function indexAction()
    {
        $client = $this->get('backend_client');
        $blogs = $client->getBlogs();

        $response = $this->render('EtienneBlogBundle:Page:index.html.twig', array('blogs' => $blogs['blogs']));
        $response->setPublic();
        $response->setMaxAge(90);
        $response->setSharedMaxAge(90);

        return $response;
    }

    public function aboutAction()
    {
        $response = $this->render('EtienneBlogBundle:Page:about.html.twig');
        $response->setPublic();
        $response->setMaxAge(86400);
        $response->setSharedMaxAge(86400);

        return $response;
    }

    public function contactAction()
    {
        $enquiry = new Enquiry();
        $form = $this->createForm(new EnquiryType(), $enquiry);

        $request = $this->getRequest();
        if ($request->getMethod() == 'POST') {
            $form->bindRequest($request);

            if ($form->isValid()) {
                $message = \Swift_Message::newInstance()
                    ->setSubject('Contact Enquiry')
                    ->setFrom('enquiries@Etienne.com')
                    ->setTo($this->container->getParameter('Etienne_blog.emails.contact_email'))
                    ->setBody($this->renderView('EtienneBlogBundle:Page:contactEmail.txt.twig', array('enquiry' => $enquiry)));

                $this->get('mailer')->send($message);

                $this->get('session')->setFlash('blogger-notice', 'Your contact enquiry was successfully sent.  Thank you!');

                return $this->redirect($this->generateUrl('EtienneBlogBundle_contact'));
            }
        }

        return $this->render('EtienneBlogBundle:Page:contact.html.twig', array('form' => $form->createView()));
    }

    public function sidebarAction() {
        $client = $this->get('backend_client');
        $comments = $client->getComments($this->container->getParameter('Etienne_blog.comments.latest_comment_limit'));
        $tagWeights = $client->getTagWeights();

        $response = $this->render('EtienneBlogBundle:Page:sidebar.html.twig', array(
            'tags'           => $tagWeights['weights'],
            'latestComments' => $comments['comments']));
        $response->setPublic();
        $response->setMaxAge(90);
        $response->setSharedMaxAge(90);

        return $response;
    }
}
