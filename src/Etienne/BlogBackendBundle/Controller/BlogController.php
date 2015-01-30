<?php

namespace Etienne\BlogBackendBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Etienne\BlogBackendBundle\Entity\Blog;
use Etienne\BlogBackendBundle\Entity\Comment;
use FOS\Rest\Util\Codes;
use FOS\RestBundle\View\View;
use Guzzle\Http\Message\Response;
use Symfony\Component\HttpFoundation\Response as SymResponse;

class BlogController extends Controller {

    /**
     * @Rest\View
     */
    public function getBlogsAction($page = 1, $per_page = 25) {
        $em = $this->getDoctrine()->getEntityManager();
        $offset = ($page - 1) * $per_page;
        return $em->getRepository('EtienneBlogBackendBundle:Blog')->findBy(array(), array('created' => 'DESC'), $per_page, $offset);
    }

    /**
     * @Rest\View
     */
    public function getBlogAction($id) {
        $request = $this->getRequest();

        $lastModified = $this->getBlogLastModified($id);

        $response = new SymResponse();
        $response->setLastModified($lastModified);
        $response->setPublic();

        if ( $response->isNotModified($this->getRequest())) {
            return $response;
        }

        $em = $this->getDoctrine()->getEntityManager();
        $blog = $em->find('EtienneBlogBackendBundle:Blog', $id);

        $view = View::create()
                 ->setStatusCode(200)
                 ->setData($blog);

        return $this->get('fos_rest.view_handler')->handle($view, $this->getRequest(), $response);
    }

    /**
     * @Rest\View
     */
    public function getCommentsAction($id) {
        $em = $this->getDoctrine()->getEntityManager();
        $blog = $em->find('EtienneBlogBackendBundle:Blog', $id);

        $lastModified = $this->getBlogLastModified($id);

        $response = new SymResponse();
        $response->setLastModified($lastModified);
        $response->setPublic();

        if ( $response->isNotModified($this->getRequest())) {
            return $response;
        }

        $comments = $em->getRepository('EtienneBlogBackendBundle:Comment')->getCommentsForBlog($id, null);

        $view = View::create()
                 ->setStatusCode(200)
                 ->setData($comments);

        return $this->get('fos_rest.view_handler')->handle($view, $this->getRequest(), $response);
    }

    public function postBlogCommentAction() {
        $request = $this->getRequest();

        $em = $this->getDoctrine()->getEntityManager();
        $blog = $em->find('EtienneBlogBackendBundle:Blog', $request->get('id'));

        $comment = new Comment();
        $comment->setBlog($blog);
        $comment->setUser($request->get('user'));
        $comment->setComment($request->get('comment'));

        $em->persist($comment);
        $em->flush();

        new Response(Codes::HTTP_CREATED);
    }

    /**
     * @Rest\View
     */
    public function getLatestCommentsAction($limit) {
        $em = $this->getDoctrine()->getEntityManager();

        $comments = $em->getRepository('EtienneBlogBackendBundle:Comment')->getLatestComments($limit);

        $response = new SymResponse();
        $response->setLastModified(count($comments) ? end($comments)->getUpdated() : new \DateTime() );
        $response->setPublic();

        if ( $response->isNotModified($this->getRequest())) {
            return $response;
        }

        $view = View::create()
                 ->setStatusCode(200)
                 ->setData($comments);

        return $this->get('fos_rest.view_handler')->handle($view, $this->getRequest(), $response);
    }

    /**
     * @Rest\View
     */
    public function getTagWeightsAction() {
        $repo = $this->getDoctrine()->getEntityManager()->getRepository('EtienneBlogBackendBundle:Blog');
        $tags = $repo->getTags();
        return $repo->getTagWeights($tags);
    }

    protected function getBlogLastModified($id) {
        $em = $this->getDoctrine()->getEntityManager();
        $blog = $em->find('EtienneBlogBackendBundle:Blog', $id);

        if (!$blog instanceof Blog ) {
            throw new NotFoundHttpException("Not an instance of blog");
        }

        $comment = $em->getRepository('EtienneBlogBackendBundle:Comment')->getCommentsForBlog($id, true);

        if (count($comment)) {
            return max($blog->getUpdated(), end($comment)->getUpdated());
        }

        return $blog->getUpdated();
    }
}
