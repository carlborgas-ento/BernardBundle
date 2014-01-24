<?php

namespace Bernard\BernardBundle\Tests\DependencyInjection;

use Bernard\BernardBundle\DependencyInjection\BernardBernardExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BernardBernardExtensionTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->extension = new BernardBernardExtension;
        $this->container = new ContainerBuilder;
    }

    public function testServicesExists()
    {
        $this->extension->load(array(array('driver' => 'doctrine')), $this->container);

        // make sure we dont have a dependencies on a real driver.
        $this->container->set('bernard.driver', $this->getMock('Bernard\Driver'));

        $this->assertInstanceOf('Bernard\Producer', $this->container->get('bernard.producer'));
        $this->assertInstanceOf('Bernard\Consumer', $this->container->get('bernard.consumer'));
        $this->assertInstanceOf('Bernard\Command\ConsumeCommand', $this->container->get('bernard.consume_command'));
        $this->assertInstanceOf('Bernard\Command\ProduceCommand', $this->container->get('bernard.produce_command'));
    }

    public function testInvalidDriver()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');

        $this->extension->load(array(array('driver' => 'invalid')), $this->container);
    }

    public function testInvalidSerializer()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');

        $this->extension->load(array(array('driver' => 'doctrine', 'serializer' => 'hopefully not valid')), $this->container);
    }

    public function testFileDriverRequiresDirectoryOptionToBeSet()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');

        $this->extension->load(array(array('driver' => 'file')), $this->container);
    }

    /**
     * @dataProvider eventListenerProvider
     */
    public function testDoctrinEventListenerIsAdded($connection)
    {
        $config = array_filter(array('driver' => 'doctrine', 'connection' => $connection));

        $this->extension->load(array($config), $this->container);

        $definition = $this->container->getDefinition('bernard.schema_listener');

        $expected = array(
            'event' => 'postGenerateSchema',
            'connection' => $connection ?: 'default',
            'lazy' => true,
        );

        $this->assertTrue($definition->hasTag('doctrine.event_listener'));
        $this->assertEquals(array($expected), $definition->getTag('doctrine.event_listener'));

        $this->extension->load(array(array('driver' => 'doctrine', 'connection' => 'bernard')), $this->container);
    }

    public function eventListenerProvider()
    {
        return array(
            array(null),
            array('default'),
            array('bernard'),
        );
    }

    public function testDirectoryIsAddedToFileDriver()
    {
        $this->extension->load(array(array('driver' => 'file', 'directory' => __DIR__)), $this->container);

        $definition = $this->container->getDefinition('bernard.driver.file');

        $this->assertCount(1, $definition->getArguments());
        $this->assertEquals(__DIR__, $definition->getArgument(0));
    }

    public function testDefaultSerializer()
    {
        $this->extension->load(array(array('driver' => 'doctrine')), $this->container);

        $alias = $this->container->getAlias('bernard.serializer');

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Alias', $alias);
        $this->assertEquals('bernard.serializer.simple', (string) $alias);
    }

    public function testDriverIsRequired()
    {
        $this->setExpectedException('Symfony\Component\Config\Definition\Exception\InvalidConfigurationException');

        $this->extension->load(array(), $this->container);
    }

    public function testDriverIsAliased()
    {
        $this->extension->load(array(array('driver' => 'doctrine')), $this->container);

        $alias = $this->container->getAlias('bernard.driver');

        $this->assertInstanceOf('Symfony\Component\DependencyInjection\Alias', $alias);
        $this->assertEquals('bernard.driver.doctrine', (string) $alias);
    }
}