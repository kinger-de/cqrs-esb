<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <manfred.weber@gmail.com> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Coverage\Cqrs\Bus;

use Cqrs\Command\InvokeCommandCommand;
use Cqrs\Command\PublishEventCommand;
use Cqrs\Event\CommandInvokedEvent;
use Cqrs\Event\EventPublishedEvent;
use Cqrs\Gate;
use Test\Coverage\Mock\Command\MockCommand;
use Test\Coverage\Mock\Event\MockEvent;

class SystemBusTest extends AbstractBusTest
{
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
        if(is_null($this->bus->getGate())){
            $this->bus->setGate(new Gate());
        }
        $this->bus->getGate()->enableSystemBus();
        $this->assertEquals('system-bus',$this->bus->getGate()->getBus('system-bus')->getName());
    }

    public function testInvokeCommand()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->mapCommand('Test\Coverage\Mock\Command\MockCommand',function(MockCommand $command){
            $command->edit();
        });
        $gate->getBus('system-bus')->mapCommand('Cqrs\Command\InvokeCommandCommand',function(InvokeCommandCommand $command){
            $this->invokeCommandCommand = $command;
        });
        $gate->getBus('system-bus')->registerEventListener('Cqrs\Event\CommandInvokedEvent',function(CommandInvokedEvent $event){
            $this->commandInvokedEvent = $event;
        });
        $mockCommand = new MockCommand();
        $this->bus->invokeCommand($mockCommand);
        $this->assertEquals(true,$mockCommand->isEdited());
        $this->assertInstanceOf('Cqrs\Command\InvokeCommandCommand',$this->invokeCommandCommand);
        $this->assertInstanceOf('Cqrs\Event\CommandInvokedEvent',$this->commandInvokedEvent);
        $this->assertEquals('Test\Coverage\Mock\Command\MockCommand',$this->invokeCommandCommand->getClass());
        $this->assertEquals('Test\Coverage\Mock\Command\MockCommand',$this->commandInvokedEvent->getClass());
    }

    public function testPublishEvent()
    {
        $gate = new Gate();
        $gate->enableSystemBus();
        $gate->attach($this->bus);
        $this->bus->registerEventListener('Test\Coverage\Mock\Event\MockEvent',function(MockEvent $event){
            $event->edit();
        });
        $gate->getBus('system-bus')->mapCommand('Cqrs\Command\PublishEventCommand',function(PublishEventCommand $command){
            $this->publishEventCommand = $command;
        });
        $gate->getBus('system-bus')->registerEventListener('Cqrs\Event\EventPublishedEvent',function(EventPublishedEvent $event){
            $this->eventPublishedEvent = $event;
        });
        $mockEvent = new MockEvent();
        $this->bus->publishEvent($mockEvent);
        $this->assertEquals(true,$mockEvent->isEdited());
        $this->assertInstanceOf('Cqrs\Command\PublishEventCommand',$this->publishEventCommand);
        $this->assertInstanceOf('Cqrs\Event\EventPublishedEvent',$this->eventPublishedEvent);
        $this->assertEquals('Test\Coverage\Mock\Event\MockEvent',$this->publishEventCommand->getClass());
        $this->assertEquals('Test\Coverage\Mock\Event\MockEvent',$this->eventPublishedEvent->getClass());
    }
}