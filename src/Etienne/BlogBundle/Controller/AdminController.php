<?php

namespace Etienne\BlogBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Etienne\BlogBundle\Form\BlogType;
use Etienne\BlogBundle\Entity\Blog;

/**
 * Admin controller.
 */
class AdminController extends Controller
{
    public function indexAction() {
        $client = $this->get('backend_client');
        $blogs = $client->getBlogs();

        return $this->render('EtienneBlogBundle:Admin:index.html.twig', array('blogs' => $blogs['blogs']));
    }

    public function deleteAction(Request $request) {
        $ids = $request->get('blogs', false);

        if(is_array($ids)) {
            $client = $this->get('backend_client');
            $client->setAuthentication($this->getApiUsername(), $this->getApiPassword());
            $client->deleteBlogs(array_keys($ids));
        }

        return $this->redirect($this->generateUrl('EtienneBlogBundle_admin', array()));
    }

    public function createAction() {
      $request = $this->getRequest();
      $form = $this->getForm();

      if('POST' == $request->getMethod()) {
          $client = $this->get('backend_client');
          $client->setAuthentication($this->getApiUsername(), $this->getApiPassword());

          $form->bindRequest($request);
          $data = $form->getData();

          try {
              $client->createBlog($data);

              $this->get('session')->setFlash('blogger-notice', 'Successfully created new blog entry');

              return $this->redirect($this->generateUrl('EtienneBlogBundle_admin', array()));
          } catch ( \Guzzle\Http\Exception\BadResponseException $e) {
              $error = '<ul>' . implode('</li><li>', json_decode($e->getResponse()->getBody(true))) . '</ul>';
              $this->get('session')->setFlash('blogger-error', $error);
          } catch ( \Exception $e) {
              $this->get('session')->setFlash('blogger-error', 'An unknown error occurred.');
          }
      }

      return $this->render('EtienneBlogBundle:Admin:create.html.twig', array('form'  => $form->createView()));
    }

    public function editAction($id) {
      $request = $this->getRequest();
      $form = $this->getForm();

      $client = $this->get('backend_client');
      $client->setAuthentication($this->getApiUsername(), $this->getApiPassword());
      $blog = $client->getBlog($id);

      if('POST' == $request->getMethod()) {
        $form->bindRequest($request);

        if($form->isValid()) {
          $data = $form->getData();

          try {
              $client->editBlog($id, array(
                                'blog'    => $data['blog'],
                                'title'   => $data['title'],
                                'tags'    => $data['tags']
                  ));

            $this->get('session')->setFlash('blogger-notice', 'Successfully update blog entry');

            return $this->redirect($this->generateUrl('EtienneBlogBundle_admin', array()));
          } catch ( \Guzzle\Http\Exception\BadResponseException $e) {
            $error = '<ul>' . implode('</li><li>', json_decode($e->getResponse()->getBody(true))) . '</ul>';
            $this->get('session')->setFlash('blogger-error', $error);
          } catch ( \Exception $e ) {
              $this->get('session')->setFlash('blogger-error', 'An unknown error occurred.');
          }
        }
      } else if ( 'GET' == $request->getMethod() ) {
        $form->setData(array(
                    'title' => $blog['blog']['title'],
                    'blog'  => $blog['blog']['blog'],
                    'tags'  => $blog['blog']['tags']
                ));
      }

      return $this->render('EtienneBlogBundle:Admin:edit.html.twig', array(
                'form'  => $form->createView(),
                'blog' => $blog['blog']
            ));
    }

    protected function getForm() {
        return $this->createFormBuilder()
            ->add('title', 'text')
            ->add('blog', 'ckeditor')
            ->add('tags', 'text')
            ->getForm();
    }

    protected function getApiUsername() {
       return $this->container->getParameter('Etienne_blog.api.admin_username');
    }

    protected function getApiPassword() {
        return $this->container->getParameter('Etienne_blog.api.admin_password');
    }
}
