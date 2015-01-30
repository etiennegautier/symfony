<?php

namespace Etienne\BlogBackendBundle\Tests\Repository;

use Etienne\BlogTestCase;

class BlogRepositoryTest extends BlogTestCase
{
    protected $repo = null;

    public function setUp()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $this->repo = $kernel->getContainer()
            ->get('doctrine.orm.entity_manager')
            ->getRepository('EtienneBlogBackendBundle:Blog');
    }

    public function testGetLastestBlogs()
    {
        $blogs = $this->repo->getLatestBlogs(2);

        $this->assertCount(2, $blogs);

        $this->assertLessThanOrEqual($blogs[0]->getCreated(), $blogs[1]->getCreated());
    }

    public function testGetTags()
    {
        $tags = $this->repo->getTags();

        $expected_tags = array('symfony2', 'php', 'misdirection', '!trusting');

        $this->assertEquals(count($expected_tags), count(array_intersect($tags, $expected_tags)));
    }

    /**
     * @dataProvider tagWeightProvider
     */
    public function testTagWeights($tags, $tagWeights)
    {
        $weights = $this->repo->getTagWeights($tags);

        foreach($weights as $tag => $weight) {
            $this->assertEquals($tagWeights[$tag], $weight);
        }
    }

    public function tagWeightProvider()
    {
        return array(
            array(array('php'), array('php' => 1)),
            array(array('symfony2', 'symfony2', 'symfony2', 'symfony2', 'symfony2', 'symfony2', 'php'), array('symfony2' => 5, 'php' => 1))
        );
    }

    public function testNoTagWeight()
    {
        $this->assertCount(0, $this->repo->getTagWeights(array()));
    }

    public function testBlogIdRetrieval()
    {
        $blog = $this->repo->find(1);

        $this->assertEquals($blog->getId(), 1);
    }

    public function testBlogToString()
    {
        $blog = $this->repo->find(1);
        $this->assertEquals('A day with Symfony2', $blog);
    }
}
