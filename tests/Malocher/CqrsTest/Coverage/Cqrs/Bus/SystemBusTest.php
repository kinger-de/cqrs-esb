<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\CqrsTest\Coverage\Cqrs\Bus;

use Malocher\Cqrs\Bus\AbstractBus;
use Malocher\Cqrs\Command\ExecuteQueryCommand;
use Malocher\Cqrs\Command\InvokeCommandCommand;
use Malocher\Cqrs\Command\PublishEventCommand;
use Malocher\Cqrs\Event\CommandInvokedEvent;
use Malocher\Cqrs\Event\EventPublishedEvent;
use Malocher\Cqrs\Event\QueryExecutedEvent;
use Malocher\Cqrs\Gate;
use Malocher\CqrsTest\Coverage\Mock\Command\MockCommand;
use Malocher\CqrsTest\Coverage\Mock\Event\MockEvent;
use Malocher\CqrsTest\Coverage\Mock\Query\MockQuery;

/**
 * Class SystemBusTest
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Malocher\CqrsTest\Coverage\Cqrs\Bus
 */
class SystemBusTest extends AbstractBusTest
{
    /**
     * @var ExecuteQueryCommand
     */
    private $executeQueryCommand;

    /**
     * @var QueryExecutedEvent
     */
    private $queryExecutedEvent;

    /**
     * @var InvokeCommandCommand
     */
    private $invokeCommandCommand;

    /**
     * @var CommandInvokedEvent
     */
    private $commandInvokedEvent;

    /**
     * @var PublishEventCommand
     */
    private $publishEventCommand;

    /**
     * @var EventPublishedEvent
     */
    private $eventPublishedEvent;

    public function testGetName()
    {
        if (is_null($this->bus->getGate())) {
            $this->bus->setGate(new Gate());
        }
        $this->bus->getGate()->enableSystemBus();
        $this->assertEquals(AbstractBus::SYSTEMBUS, $this->bus->getGate()->getSystemBus()->getName());
    }

    public function testClosureInvokeCommand()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapCommand('Malocher\CqrsTest\Coverage\Mock\Command\MockCommand', function (MockCommand $command) {
            $command->edit();
        });
        $gate->getSystemBus()->mapCommand('Malocher\Cqrs\Command\InvokeCommandCommand', function (InvokeCommandCommand $command) {
            $this->invokeCommandCommand = $command;
        });
        $gate->getSystemBus()->registerEventListener('Malocher\Cqrs\Event\CommandInvokedEvent', function (CommandInvokedEvent $event) {
            $this->commandInvokedEvent = $event;
        });
        $mockCommand = new MockCommand();
        $gate->getBus($this->bus->getName())->invokeCommand($mockCommand);
        $this->assertEquals(true, $mockCommand->isEdited());
        $this->assertInstanceOf('Malocher\Cqrs\Command\InvokeCommandCommand', $this->invokeCommandCommand);
        $this->assertInstanceOf('Malocher\Cqrs\Event\CommandInvokedEvent', $this->commandInvokedEvent);
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Command\MockCommand', $this->invokeCommandCommand->getMessageClass());
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Command\MockCommand', $this->commandInvokedEvent->getMessageClass());
    }

    public function testClosureExecuteQuery()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapQuery('Malocher\CqrsTest\Coverage\Mock\Query\MockQuery', function (MockQuery $query) {
            $query->edit();
        });
        $gate->getSystemBus()->mapCommand('Malocher\Cqrs\Command\ExecuteQueryCommand', function (ExecuteQueryCommand $command) {
            $this->executeQueryCommand = $command;
        });
        $gate->getSystemBus()->registerEventListener('Malocher\Cqrs\Event\QueryExecutedEvent', function (QueryExecutedEvent $event) {
            $this->queryExecutedEvent = $event;
        });
        $mockQuery = new MockQuery();
        $gate->getBus($this->bus->getName())->executeQuery($mockQuery);
        $this->assertEquals(true, $mockQuery->isEdited());
        $this->assertInstanceOf('Malocher\Cqrs\Command\ExecuteQueryCommand', $this->executeQueryCommand);
        $this->assertInstanceOf('Malocher\Cqrs\Event\QueryExecutedEvent', $this->queryExecutedEvent);
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Query\MockQuery', $this->executeQueryCommand->getMessageClass());
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Query\MockQuery', $this->queryExecutedEvent->getMessageClass());
    }

    public function testClosurePublishEvent()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->registerEventListener('Malocher\CqrsTest\Coverage\Mock\Event\MockEvent', function (MockEvent $event) {
            $event->edit();
        });
        $gate->getSystemBus()->mapCommand('Malocher\Cqrs\Command\PublishEventCommand', function (PublishEventCommand $command) {
            $this->publishEventCommand = $command;
        });
        $gate->getSystemBus()->registerEventListener('Malocher\Cqrs\Event\EventPublishedEvent', function (EventPublishedEvent $event) {
            $this->eventPublishedEvent = $event;
        });
        $mockEvent = new MockEvent();
        $gate->getBus($this->bus->getName())->publishEvent($mockEvent);
        $this->assertEquals(true, $mockEvent->isEdited());
        $this->assertInstanceOf('Malocher\Cqrs\Command\PublishEventCommand', $this->publishEventCommand);
        $this->assertInstanceOf('Malocher\Cqrs\Event\EventPublishedEvent', $this->eventPublishedEvent);
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Event\MockEvent', $this->publishEventCommand->getMessageClass());
        $this->assertEquals('Malocher\CqrsTest\Coverage\Mock\Event\MockEvent', $this->eventPublishedEvent->getMessageClass());
    }

    public function testArrayMapExecuteQuery()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapQuery(
            'Malocher\CqrsTest\Coverage\Mock\Query\MockQuery',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Query\MockQueryHandler', 'method' => 'handleQuery')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\ExecuteQueryCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\QueryExecutedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $mockQuery = new MockQuery();
        $result = $this->bus->executeQuery($mockQuery);
        $this->assertEquals(array(1, 2, 3, 4, 5), $result);
        $this->assertEquals(true, $mockQuery->isEdited());
    }

    public function testArrayMapInvokeCommand()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapCommand(
            'Malocher\CqrsTest\Coverage\Mock\Command\MockCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\InvokeCommandCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\CommandInvokedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $mockCommand = new MockCommand();
        $this->bus->invokeCommand($mockCommand);
        $this->assertEquals(true, $mockCommand->isEdited());
    }

    public function testArrayMapPublishEvent()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->registerEventListener(
            'Malocher\CqrsTest\Coverage\Mock\Event\MockEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\PublishEventCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\EventPublishedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $mockEvent = new MockEvent();
        $this->bus->publishEvent($mockEvent);
        $this->assertEquals(true, $mockEvent->isEdited());
    }

    public function testArrayMapExecuteQueryMissingAdapterTrait()
    {
        $this->setExpectedException('Malocher\Cqrs\Bus\BusException');
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapQuery(
            'Malocher\CqrsTest\Coverage\Mock\Query\MockQuery',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Query\MockQueryHandler', 'method' => 'handleQuery')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\ExecuteQueryCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandlerNoAdapter', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\QueryExecutedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $mockQuery = new MockQuery();
        $result = $gate->getBus($this->bus->getName())->executeQuery($mockQuery);
        $this->assertEquals(array(1, 2, 3, 4, 5), $result);
    }

    public function testArrayMapInvokeCommandMissingAdapterTrait()
    {
        $this->setExpectedException('Malocher\Cqrs\Bus\BusException');
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapCommand(
            'Malocher\CqrsTest\Coverage\Mock\Command\MockCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\InvokeCommandCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandlerNoAdapter', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\CommandInvokedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $mockCommand = new MockCommand();
        $gate->getBus($this->bus->getName())->invokeCommand($mockCommand);
    }

    public function testArrayMapPublishEventMissingAdapterTrait()
    {
        $this->setExpectedException('Malocher\Cqrs\Bus\BusException');
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->registerEventListener(
            'Malocher\CqrsTest\Coverage\Mock\Event\MockEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandler', 'method' => 'handleEvent')
        );
        $gate->getSystemBus()->mapCommand(
            'Malocher\Cqrs\Command\PublishEventCommand',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Command\MockCommandHandler', 'method' => 'handleCommand')
        );
        $gate->getSystemBus()->registerEventListener(
            'Malocher\Cqrs\Event\EventPublishedEvent',
            array('alias' => 'Malocher\CqrsTest\Coverage\Mock\Event\MockEventHandlerNoAdapter', 'method' => 'handleEvent')
        );
        $mockEvent = new MockEvent();
        $gate->getBus($this->bus->getName())->publishEvent($mockEvent);
    }

    public function testExecuteNonMappedQuery()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $mockQuery = new MockQuery();
        $this->assertFalse($gate->getSystemBus()->executeQuery($mockQuery));
    }

    public function testInvokeNonMappedCommand()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $mockCommand = new MockCommand();
        $this->assertFalse($gate->getSystemBus()->invokeCommand($mockCommand));
    }

    public function testInvokeNonMappedEvent()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $mockEvent = new MockEvent();
        $this->assertFalse($gate->getSystemBus()->publishEvent($mockEvent));
    }

}