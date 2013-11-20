<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Coverage\Cqrs\Bus;

use Cqrs\Adapter\AnnotationAdapter;
use Cqrs\Bus\AbstractBus;
use Cqrs\Command\ClassMapCommandHandlerLoader;
use Cqrs\Command\CommandInterface;
use Cqrs\Event\ClassMapEventListenerLoader;
use Cqrs\Event\EventInterface;
use Cqrs\Gate;
use Cqrs\Query\ClassMapQueryHandlerLoader;
use Cqrs\Query\QueryInterface;
use Test\Coverage\Mock\Command\MockCommand;
use Test\Coverage\Mock\Event\MockEvent;
use Test\Coverage\Mock\Query\MockQuery;
use Test\TestCase;

/**
 * Class AbstractBusTest
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Test\Coverage\Cqrs\Bus
 */
class AbstractBusTest extends TestCase implements BusInterfaceTest
{
    /**
     * @var AbstractBus
     */
    public $bus;

    public function setUp()
    {
        $this->bus = $this->getMockForAbstractClass('Cqrs\Bus\AbstractBus');
        
        $this->bus->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $this->bus->setEventListenerLoader(new ClassMapEventListenerLoader());
        $this->bus->setQueryHandlerLoader(new ClassMapQueryHandlerLoader());
    }

    public function testConstructed()
    {
        $this->assertInstanceOf('Cqrs\Bus\AbstractBus', $this->bus);
    }

    public function testSetGate()
    {
        $this->bus->setGate(new Gate());
        $this->assertInstanceOf('Cqrs\Gate', $this->bus->getGate());
    }

    public function testGetGate()
    {
        if (is_null($this->bus->getGate())) {
            $this->bus->setGate(new Gate());
        }
        $this->assertInstanceOf('Cqrs\Gate', $this->bus->getGate());
    }

    public function testMapQuery()
    {
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', function (QueryInterface $query) {
        });
        $this->assertNotNull($this->bus->getQueryHandlerMap()['Test\Coverage\Mock\Query\MockQuery']);
    }

    public function testGetQueryHandlerMap()
    {
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', function (QueryInterface $query) {
        });
        $this->assertEquals(1, count($this->bus->getQueryHandlerMap()));
    }

    public function testExecuteQuery()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', function (MockQuery $query) {
            $query->edit();
            return array(1, 2, 3, 4, 5);
        });
        $mockQuery = new MockQuery();        
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertEquals($result, array(1, 2, 3, 4, 5));
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testExecuteQueryNoResult()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', function (MockQuery $query) {
            $query->edit();
            return null;
        });
        $mockQuery = new MockQuery();
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertNull($result);
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testArrayExecuteQuery()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', array(
            'alias' => 'Test\Coverage\Mock\Query\MockQueryHandler',
            'method' => 'handleQuery'
        ));
        $mockQuery = new MockQuery();
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertEquals($result, array(1, 2, 3, 4, 5));
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testArrayExecuteQueryNoResult()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', array(
            'alias' => 'Test\Coverage\Mock\Query\MockQueryHandler',
            'method' => 'handleQueryWithNoResult'
        ));
        $mockQuery = new MockQuery();
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertNull($result);
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testExecuteQueryHandlerAnnotationAdapter()
    {
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Query\MockCallbackQueryHandler'));
        $mockQuery = new MockQuery();
        $mockQuery->callback = function ($isEdited) {
        };
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertEquals($result, array(1, 2, 3, 4, 5));
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testExecuteQueryHandlerNoAdapter()
    {
        $this->setExpectedException('Cqrs\Bus\BusException');
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Query\MockQueryHandlerNoAdapter'));
        $mockQuery = new MockQuery();
        $mockQuery->callback = function ($isEdited) {
        };
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertEquals($result, array(1, 2, 3, 4, 5));
    }
    
    public function testExcecuteQueryHandlerNoQueryHandlerLoader()
    {
        $this->setExpectedException('Cqrs\Bus\BusException');
        
        $bus = $this->getMockForAbstractClass('Cqrs\Bus\AbstractBus'); 
        
        $bus->setGate(new Gate());
        $bus->mapQuery('Test\Coverage\Mock\Query\MockQuery', array(
            'alias' => 'Test\Coverage\Mock\Query\MockQueryHandler',
            'method' => 'handleQueryWithNoResult'
        ));
        $mockQuery = new MockQuery();
        $result = $bus->executeQuery($mockQuery);
    }

    public function testMapCommand()
    {
        $this->bus->mapCommand('Test\Coverage\Mock\Command\MockCommand', function (CommandInterface $command) {
        });
        $this->assertNotNull($this->bus->getCommandHandlerMap()['Test\Coverage\Mock\Command\MockCommand']);
    }

    public function testGetCommandHandlerMap()
    {
        $this->bus->mapCommand('Test\Coverage\Mock\Command\MockCommand', function (CommandInterface $command) {
        });
        $this->assertEquals(1, count($this->bus->getCommandHandlerMap()));
    }

    public function testInvokeCommand()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->mapCommand('Test\Coverage\Mock\Command\MockCommand', function (MockCommand $command) {
            $command->edit();
        });
        $mockCommand = new MockCommand();
        $this->bus->invokeCommand($mockCommand);
        $this->assertEquals(true, $mockCommand->isEdited());
    }

    public function testInvokeCommandHandlerAnnotationAdapter()
    {
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Command\MockCallbackCommandHandler'));
        $mockCommand = new MockCommand();
        $mockCommand->callback = function ($isEdited) {
        };
        $this->bus->invokeCommand($mockCommand);
        $this->assertEquals(true, $mockCommand->isEdited());
    }

    public function testInvokeCommandHandlerNoAdapter()
    {
        $this->setExpectedException('Cqrs\Bus\BusException');
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Command\MockCommandHandlerNoAdapter'));
        $mockCommand = new MockCommand();
        $mockCommand->callback = function ($isEdited) {
        };
        $this->bus->invokeCommand($mockCommand);
    }
    
    public function testInvokeCommandHanlderNoCommandHandlerLoader()
    {        
        $this->setExpectedException('Cqrs\Bus\BusException');
        
        $bus = $this->getMockForAbstractClass('Cqrs\Bus\AbstractBus'); 
        
        $bus->setGate(new Gate());
        $bus->mapCommand('Test\Coverage\Mock\Command\MockCommand', array(
            'alias' => 'Test\Coverage\Mock\Command\MockCommandHandler',
            'method' => 'handleCommand'
        ));
        $mockCommand = new MockCommand();
        $bus->invokeCommand($mockCommand);    
    }

    public function testRegisterEventListener()
    {
        $this->bus->registerEventListener('Test\Coverage\Mock\Event\MockEvent', function (EventInterface $event) {
        });
        $this->assertNotNull($this->bus->getEventListenerMap()['Test\Coverage\Mock\Event\MockEvent']);
    }

    public function testGetEventListenerMap()
    {
        $this->bus->registerEventListener('Test\Coverage\Mock\Event\MockEvent', function (EventInterface $event) {
        });
        $this->assertEquals(1, count($this->bus->getEventListenerMap()));
    }

    public function testPublishEvent()
    {
        $gate = new Gate();
        $gate->attach($this->bus);
        $this->bus->registerEventListener('Test\Coverage\Mock\Event\MockEvent', function (MockEvent $event) {
            $event->edit();
        });
        $mockEvent = new MockEvent();
        $this->bus->publishEvent($mockEvent);
        $this->assertEquals(true, $mockEvent->isEdited());
    }

    public function testPublishEventHandlerAnnotationAdapter()
    {
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Event\MockCallbackEventHandler'));
        $mockEvent = new MockEvent();
        $mockEvent->callback = function ($isEdited) {
        };
        $this->bus->publishEvent($mockEvent);
        $this->assertEquals(true, $mockEvent->isEdited());
    }

    public function testPublishEventHandlerNoAdapter()
    {
        $this->setExpectedException('Cqrs\Bus\BusException');
        $this->bus->setGate(new Gate());
        $adapter = new AnnotationAdapter();
        $adapter->pipe($this->bus, array('Test\Coverage\Mock\Event\MockEventHandlerNoAdapter'));
        $mockEvent = new MockEvent();
        $mockEvent->callback = function ($isEdited) {
        };
        $this->bus->publishEvent($mockEvent);
        $this->assertEquals(true, $mockEvent->isEdited());
    }

    public function testInvokeNonMappedCommand()
    {
        $this->bus->setGate(new Gate());
        $mockCommand = new MockCommand();
        $this->assertFalse($this->bus->invokeCommand($mockCommand));
    }

    public function testInvokeNonMappedEvent()
    {
        $this->bus->setGate(new Gate());
        $mockEvent = new MockEvent();
        $this->assertFalse($this->bus->publishEvent($mockEvent));
    }
    
    public function testPublishEventHandlerNoEventHandlerLoader()
    {
        $this->setExpectedException('Cqrs\Bus\BusException');
        
        $bus = $this->getMockForAbstractClass('Cqrs\Bus\AbstractBus'); 
        
        $bus->setGate(new Gate());
        $bus->registerEventListener('Test\Coverage\Mock\Event\MockEvent', array(
            'alias' => 'Test\Coverage\Mock\Event\MockEventHandler',
            'method' => 'handleEvent'
        ));
        $mockEvent = new MockEvent();
        $bus->publishEvent($mockEvent);    
    }
}
