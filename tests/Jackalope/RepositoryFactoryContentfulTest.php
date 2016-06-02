<?php

namespace Jackalope;
use Jackalope\Transport\RepositoryFactoryContentful;

/**
 * @author Maximilian Berghoff <Maximilian.Berghoff@mayflower.de>
 */
class RepositoryFactoryContentfulTest extends \PHPUnit_Framework_TestCase
{
    public function testMissingRequired()
    {
        $factory = new RepositoryFactoryContentful();
        $repository = $factory->getRepository(array());

        $this->assertNull($repository);
    }

    public function testSuccessfullRepositoryCreation()
    {
        $connection = $this->prophesize('\Doctrine\DBAL\Connection');
        $factory = new RepositoryFactoryContentful($connection);
        $repository = $factory->getRepository(array(
            RepositoryFactoryContentful::KEY_JACKALOPE_CONTENTFUL_SPACE_ID => 'some-space',
            RepositoryFactoryContentful::KEY_JACKALOPE_CONTENTFUL_ACCESS_TOKEN => 'some-token',
        ));

        $this->assertNotNull($repository);
    }
}
