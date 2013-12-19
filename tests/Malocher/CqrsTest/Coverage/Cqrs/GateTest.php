<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\CqrsTest\Coverage\Cqrs;

use Malocher\Cqrs\Command\ClassMapCommandHandlerLoader;
use Malocher\Cqrs\Event\ClassMapEventListenerLoader;
use Malocher\Cqrs\Bus\SystemBus;
use Malocher\Cqrs\Gate;
use Malocher\Cqrs\Query\ClassMapQueryHandlerLoader;
use Malocher\CqrsTest\Coverage\Mock\Bus\MockBus;
use Malocher\CqrsTest\Coverage\Mock\Bus\MockFakeSystemBus;
use Malocher\CqrsTest\TestCase;

/**
 * Class GateTest
 *
 * @author Manfred Weber <crafics@php.net>
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Malocher\CqrsTest\Coverage\Cqrs
 */
class GateTest extends TestCase
{
    /**
     * @var Gate
     */
    private $gate;

    public function setUp()
    {
        $this->gate = new Gate();
    }

    public function testConstructed()
    {
        $this->assertInstanceOf('Malocher\Cqrs\Gate', $this->gate);
    }

    public function testReset()
    {
        $mockBus = new MockBus();
        $mockBus->setCommandHandlerLoader(new ClassMapCommandHandlerLoader());
        $mockBus->setEventListenerLoader(new ClassMapEventListenerLoader());
        $mockBus->setQueryHandlerLoader(new ClassMapQueryHandlerLoader());
        
        $this->gate->attach($mockBus);
        $this->gate->reset();
        $attachedBuses = $this->gate->attachedBuses();
        $this->assertEquals(0, count($attachedBuses));
    }

    public function testResetSystemBus()
    {
        $this->gate->enableSystemBus();
        $this->gate->reset();
        $this->assertInstanceOf('Malocher\Cqrs\Bus\SystemBus', $this->gate->getSystemBus());
        $this->gate->disableSystemBus();
        $this->assertNull($this->gate->getSystemBus());
    }

    public function testEnableSystemBus()
    {
        $this->gate->enableSystemBus();
        $systemBus = $this->gate->getSystemBus();
        $this->assertInstanceOf('Malocher\Cqrs\Bus\SystemBus', $systemBus);
    }

    public function testGetSystemBus()
    {
        $this->gate->enableSystemBus();
        $this->assertInstanceOf('Malocher\Cqrs\Bus\SystemBus', $this->gate->getSystemBus());
    }

    public function testGetNonInitializedSystemBus()
    {
        $this->gate->disableSystemBus();
        $this->assertNull($this->gate->getSystemBus());
    }

    public function testAttachFakeSystemBus()
    {
        $this->setExpectedException('Malocher\Cqrs\Gate\GateException');
        $this->gate->enableSystemBus();
        $mockFakeSystemBus = new MockFakeSystemBus();
        $this->gate->attach($mockFakeSystemBus);
    }
    
    public function testAttachDuplicateSystemBus()
    {
        $this->setExpectedException('Malocher\Cqrs\Gate\GateException');
        $this->gate->enableSystemBus();
        $this->gate->attach(new SystemBus());
    }

    public function testDisableSystemBus()
    {
        if (is_null($this->gate->getSystemBus())) {
            $this->gate->enableSystemBus();
        }
        $this->gate->disableSystemBus();
        $systemBus = $this->gate->getSystemBus();
        $this->assertNull($systemBus);
    }

    public function testDetach()
    {
        $this->setExpectedException('Malocher\Cqrs\Bus\BusException');
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        $this->gate->attach($mockBus);
        
        $this->gate->detach($mockBus);
        $this->gate->getBus('test-coverage-mock-bus');
    }

    public function testAttachedBuses()
    {
        $this->gate->reset();
        $this->assertEquals(0, count($this->gate->attachedBuses()));
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        $this->gate->attach($mockBus);
        $this->assertEquals(1, count($this->gate->attachedBuses()));
    }

    public function testAttach()
    {
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        $this->gate->attach($mockBus);
        $this->assertEquals(
            $this->gate->getBus('test-coverage-mock-bus')->getName(),
            $mockBus->getName()
        );
    }

    public function testAttachSameBusTwice()
    {
        $this->setExpectedException('Malocher\Cqrs\Gate\GateException');
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        $this->gate->attach($mockBus);
        $this->gate->attach($mockBus);
    }

    public function testGetBus()
    {        
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $this->gate->attach($mockBus);
        
        $this->assertEquals(
            $this->gate->getBus('test-coverage-mock-bus')->getName(),
            $mockBus->getName()
        );
    }
    
    public function testGetBus_oneBusIsDefault()
    {
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $this->gate->attach($mockBus);
        
        $this->assertEquals(
            $this->gate->getBus()->getName(),
            $mockBus->getName()
        );
    }
    
    public function testGetBus_DefaultBusSet()
    {
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $anotherBus = new \Malocher\CqrsTest\Coverage\Mock\Bus\MockAnotherBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $this->gate->attach($mockBus);
        $this->gate->attach($anotherBus);
        $this->gate->setDefaultBusName($mockBus->getName());
        
        $this->assertEquals(
            $this->gate->getBus()->getName(),
            $mockBus->getName()
        );
    }
    
    public function testGetBus_ErrorWhenNoDefaultBusIsDefined()
    {
        $this->setExpectedException('Malocher\Cqrs\Bus\BusException');
        
        $mockBus = new MockBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $anotherBus = new \Malocher\CqrsTest\Coverage\Mock\Bus\MockAnotherBus(
            new ClassMapCommandHandlerLoader(),
            new ClassMapEventListenerLoader(),
            new ClassMapQueryHandlerLoader()
        );
        
        $this->gate->attach($mockBus);
        $this->gate->attach($anotherBus);
                
        $this->assertEquals(
            $this->gate->getBus(),
            $mockBus
        );
    }
}