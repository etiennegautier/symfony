<?php

namespace Etienne\BlogBackendBundle\Tests\Controller;

use Etienne\BlogBackendBundle\Controller\BlogController;

use Etienne\BlogTestCase;

class BlogControllerTest extends BlogTestCase {
    protected $client;
    protected $router;

    public function setUp() {
        $this->client = static::createClient(array(), array('CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'));
        $this->router = static::$kernel->getContainer()->get('router');
    }

    public function testRetrievePaginatedBlogs() {
        $this->client->request('GET',
            $this->router->generate('EtienneBlogBackendBundle_blogs', array('page' => 1, 'per_page' => 2)),
            array(),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $content);

        $firstCreated = \DateTime::createFromFormat(\DateTime::ATOM, $content[0]->created);
        $secondCreated = \DateTime::createFromFormat(\DateTime::ATOM, $content[1]->created);
        $this->assertLessThanOrEqual($firstCreated, $secondCreated);
    }

    public function testRetrieveBlog() {
        $this->client->request('GET',
            $this->router->generate('EtienneBlogBackendBundle_blog', array('id' => 1)),
            array(),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertEquals('A day with Symfony2', $content->title);
    }

    public function testGetComments() {
        $this->client->request('GET',
            $this->router->generate('EtienneBlogBackendBundle_comments', array('id' => 1)),
            array(),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent());
        $comment = $content[0];
        $this->assertEquals('A day with Symfony2', $comment->blog->title);
    }

    public function testLatestCommentsAction() {
        $this->client->request('GET',
            $this->router->generate('EtienneBlogBackendBundle_latestComments', array('limit' => 2)),
            array(),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $content = json_decode($this->client->getResponse()->getContent());
        $this->assertCount(2, $content);

        $firstCreated = \DateTime::createFromFormat(\DateTime::ATOM, $content[0]->created);
        $secondCreated = \DateTime::createFromFormat(\DateTime::ATOM, $content[1]->created);
        $this->assertLessThanOrEqual($firstCreated, $secondCreated);
    }

    public function testGetTagWeights() {
        $this->client->request('GET',
            $this->router->generate('EtienneBlogBackendBundle_tagWeights'),
            array(),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $content = json_decode($this->client->getResponse()->getContent(), true);

        $tags = array(
            'leaky', 'magic', 'binary', 'grid', 'pool', 'paradise', 'dead', 'hacked', 'alive',
            'movie', 'daftpunk', 'misdirection', 'hacking', 'symfony2', 'one', 'zero', 'php',
            '!trusting', 'symblog'
        );

        $this->assertCount(0, array_diff($tags, array_keys($content)));
    }

    public function testPostBlogComment() {
        $this->client->request('POST', $this->router->generate('EtienneBlogBackendBundle_create_comments'),
            array('user' => 'keelerm', 'comment' => 'Here is a comment', 'id' => 1),
            array()
        );

        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }
}
