<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Cqrs;

use Cqrs\Bus\BusInterface;
use Cqrs\Bus\SystemBus;
use Cqrs\Command\ClassMapCommandHandlerLoader;
use Cqrs\Event\ClassMapEventListenerLoader;
use Cqrs\Gate\GateException;

/**
 * Class Gate
 *
 * @author Manfred Weber <crafics@php.net>
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Cqrs
 */
class Gate
{

    /**
     * Buses
     *
     * @var array Bus
     */
    private $buses;

    /**
     * Private constructor
     */
    public function __construct()
    {
        $this->buses = array();
    }

    /**
     * reset singleton
     * @return $this
     */
    public function reset()
    {
        foreach ($this->buses as $bus) {
            if ($bus->getName() === 'system-bus') {
                continue;
            }
            $this->detach($bus);
        }
        return $this;
    }

    /**
     * enable system bus
     */
    public function enableSystemBus()
    {
        if (is_null($this->getBus('system-bus'))) {
            $systemBus = new SystemBus(
                new ClassMapCommandHandlerLoader(),
                new ClassMapEventListenerLoader()
            );
            $this->attach($systemBus);
        }
    }

    /**
     * @return BusInterface
     */
    public function getSystemBus()
    {
        return $this->getBus('system-bus');
    }

    /**
     * disable system bus
     */
    public function disableSystemBus()
    {
        $systemBus = $this->getSystemBus();
        if (isset($systemBus)) {
            $this->detach($systemBus);
        };
    }

    /**
     * detach bus
     *
     * @param BusInterface $bus
     */
    public function detach(BusInterface $bus)
    {
        if (isset($this->buses[$bus->getName()])) {
            $this->buses[$bus->getName()] = null;
            unset($this->buses[$bus->getName()]);
        }
    }

    /**
     * get attached buses
     *
     * @return array
     */
    public function attachedBuses()
    {
        return $this->buses;
    }

    /**
     * attach bus
     *
     * @param BusInterface $bus
     * @throws Gate\GateException
     */
    public function attach(BusInterface $bus)
    {
        $bus->setGate($this);
        if (isset($this->buses[$bus->getName()])) {
            switch ($bus->getName()) {
                case 'system-bus':
                    throw GateException::attachError(sprintf('Bus <%s> is reserved!', $bus->getName()));
                default:
                    throw GateException::attachError(sprintf('Bus <%s> is already attached!', $bus->getName()));
            }
        }
        $this->buses[$bus->getName()] = $bus;
    }

    /**
     * get bus
     *
     * @param string $name
     * @return BusInterface
     */
    public function getBus($name)
    {
        if (!isset($this->buses[$name])) {
            return null;
        }
        return $this->buses[$name];
    }

}