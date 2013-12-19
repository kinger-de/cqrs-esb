<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\CqrsTest\Coverage\Cqrs\Configuration;

use Malocher\Cqrs\Command\ClassMapCommandHandlerLoader;
use Malocher\Cqrs\Configuration\Setup;
use Malocher\Cqrs\Event\ClassMapEventListenerLoader;
use Malocher\Cqrs\Gate;
use Malocher\Cqrs\Query\ClassMapQueryHandlerLoader;
use Malocher\CqrsTest\Coverage\Mock\Command\MockCommand;
use Malocher\CqrsTest\Coverage\Mock\Command\MockCommandMonitor;
use Malocher\CqrsTest\Coverage\Mock\Event\MockEvent;
use Malocher\CqrsTest\TestCase;

/**
 * Class SetupTest
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Malocher\CqrsTest\Coverage\Cqrs\Configuration
 */
class SetupTest extends TestCase
{
    /**
     * @var Setup
     */
    private $setup;

    public function setUp()
    {
        $this->setup = new Setup();
    }

    public function testConstructed()
    {
        $this->assertInstanceOf('Malocher\Cqrs\Configuration\Setup', $this->setup);
    }

    public function testSetGate()
    {
        $gate = new Gate();
        $this->setup->setGate($gate);
        $this->assertEquals($gate, $this->setup->getGate());
        $this->assertInstanceOf('Malocher\Cqrs\Gate', $this->setup->getGate());
    }

    public function testGetGate()
    {
        if (is_null($this->setup->getGate())) {
            $this->setup->setGate(new Gate());
        }
        $this->assertInstanceOf('Malocher\Cqrs\Gate', $this->setup->getGate());
    }

    public function testSetCommandHandlerLoader()
    {
        $this->setup->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $this->assertInstanceOf('Malocher\Cqrs\Command\ClassMapCommandHandlerLoader', $this->setup->getCommandHandlerLoader());
    }

    public function testGetCommandHandlerLoader()
    {
        if (is_null($this->setup->getCommandHandlerLoader())) {
            $this->setup->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        }
        $this->assertInstanceOf('Malocher\Cqrs\Command\ClassMapCommandHandlerLoader', $this->setup->getCommandHandlerLoader());
    }

    public function testSetEventListenerLoader()
    {
        $this->setup->setEventListenerLoader(new ClassMapEventListenerLoader());
        $this->assertInstanceOf('Malocher\Cqrs\Event\ClassMapEventListenerLoader', $this->setup->getEventListenerLoader());
    }

    public function testGetEventListenerLoader()
    {
        if (is_null($this->setup->getEventListenerLoader())) {
            $this->setup->setEventListenerLoader(new ClassMapEventListenerLoader());
        }
        $this->assertInstanceOf('Malocher\Cqrs\Event\ClassMapEventListenerLoader', $this->setup->getEventListenerLoader());
    }

    public function testSetGetQueryHandlerLoader()
    {
        $this->setup->setQueryHandlerLoader(new ClassMapQueryHandlerLoader());
        $this->assertInstanceOf('Malocher\Cqrs\Query\QueryHandlerLoaderInterface', $this->setup->getQueryHandlerLoader());
    }

    public function testInitialize()
    {
        $monitor = new MockCommandMonitor();

        $configuration = array(
            'default_bus' => 'test-coverage-mock-bus',
            'adapters' => array(
                'Malocher\Cqrs\Adapter\ArrayMapAdapter' => array(
                    'buses' => array(
                        'Malocher\CqrsTest\Coverage\Mock\Bus\MockBus' => array(
                            'Malocher\CqrsTest\Coverage\Mock\Command\MockCommand' => array(
                                'alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler',
                                'method' => 'handleCommand'
                            )
                        ),
                        'Malocher\CqrsTest\Coverage\Mock\Bus\MockAnotherBus' => array(
                            'Malocher\CqrsTest\Coverage\Mock\Event\MockEvent' => array(
                                'alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler',
                                'method' => 'handleEvent'
                            )
                        ),
                        'Malocher\Cqrs\Bus\SystemBus' => array(
                            'Malocher\Cqrs\Command\InvokeCommandCommand' => $monitor,
                            'Malocher\Cqrs\Event\CommandInvokedEvent' => $monitor,
                        )
                    )
                ),
            ),
        );
        $this->setup->setGate(new Gate());
        $this->setup->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $this->setup->setEventListenerLoader(new ClassMapEventListenerLoader());
        $this->setup->setQueryHandlerLoader(new ClassMapQueryHandlerLoader());
        $this->setup->initialize($configuration);

        $this->assertInstanceOf('Malocher\Cqrs\Gate', $this->setup->getGate());
        $this->assertInstanceOf('Malocher\Cqrs\Command\CommandHandlerLoaderInterface', $this->setup->getCommandHandlerLoader());
        $this->assertInstanceOf('Malocher\Cqrs\Event\EventListenerLoaderInterface', $this->setup->getEventListenerLoader());
        $this->assertInstanceOf('Malocher\Cqrs\Bus\BusInterface', $this->setup->getGate()->getBus('test-coverage-mock-bus'));

        $this->assertInstanceOf('Malocher\Cqrs\Bus\SystemBus', $this->setup->getGate()->getSystemBus());

        $mockCommand = new MockCommand();

        $this->setup->getGate()->getBus('test-coverage-mock-bus')->invokeCommand($mockCommand);

        $this->assertTrue($mockCommand->isEdited());

        $invokeCommandCommand = $monitor->getInvokeCommandCommands()[0];
        $commandInvokedEvent = $monitor->getCommandInvokedEvents()[0];

        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Command\MockCommand', $invokeCommandCommand->getMessageClass());
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Command\MockCommand', $commandInvokedEvent->getMessageClass());
        
        //test setup multiple buses
        $mockEvent = new MockEvent();
        
        $this->setup->getGate()->getBus('test-coverage-mock-another-bus')->publishEvent($mockEvent);
        $this->assertTrue($mockEvent->isEdited());
        
        //Test setup the default bus corectly
        $this->assertInstanceOf('Malocher\Cqrs\Bus\BusInterface', $this->setup->getGate()->getBus());
    }

    public function testInitializeWithoutGate()
    {
        $this->setExpectedException('Malocher\Cqrs\Configuration\ConfigurationException');
        $this->setup->initialize(array());
    }

    public function testInitializeWithoutCommandHandlerLoader()
    {
        $this->setExpectedException('Malocher\Cqrs\Configuration\ConfigurationException');
        $configuration = array(
            'enable_system_bus' => true,
            'adapters' => array(
                'Malocher\Cqrs\Adapter\ArrayMapAdapter' => array(
                    'buses' => array(
                        'Malocher\CqrsTest\Coverage\Mock\Bus\MockBus' => array(
                            'Malocher\CqrsTest\Coverage\Mock\Command\MockCommand' => array(
                                'alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler',
                                'method' => 'handleCommand'
                            )
                        )
                    )
                ),
            ),
        );
        $this->setup->setGate(new Gate());
        $this->setup->setEventListenerLoader(new ClassMapEventListenerLoader());
        $this->setup->initialize($configuration);
    }

    public function testInitializeWithoutEventHandlerLoader()
    {
        $this->setExpectedException('Malocher\Cqrs\Configuration\ConfigurationException');
        $configuration = array(
            'enable_system_bus' => true,
            'adapters' => array(
                'Malocher\Cqrs\Adapter\ArrayMapAdapter' => array(
                    'buses' => array(
                        'Malocher\CqrsTest\Coverage\Mock\Bus\MockBus' => array(
                            'Malocher\CqrsTest\Coverage\Mock\Command\MockCommand' => array(
                                'alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler',
                                'method' => 'handleCommand'
                            )
                        )
                    )
                ),
            ),
        );
        $this->setup->setGate(new Gate());
        $this->setup->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $this->setup->initialize($configuration);
    }
    
    public function testInitializeWithoutQuerytHandlerLoader()
    {
        $this->setExpectedException('Malocher\Cqrs\Configuration\ConfigurationException');
        $configuration = array(
            'enable_system_bus' => true,
            'adapters' => array(
                'Malocher\Cqrs\Adapter\ArrayMapAdapter' => array(
                    'buses' => array(
                        'Malocher\CqrsTest\Coverage\Mock\Bus\MockBus' => array(
                            'Malocher\CqrsTest\Coverage\Mock\Query\MockQuery' => array(
                                'alias' => 'Malocher\CqrsTest\Coverage\Mock\Query\MockQueryHandler',
                                'method' => 'handleQuery'
                            )
                        )
                    )
                ),
            ),
        );
        $this->setup->setGate(new Gate());
        $this->setup->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $this->setup->setEventListenerLoader(new ClassMapEventListenerLoader());
        $this->setup->initialize($configuration);
    }
}
