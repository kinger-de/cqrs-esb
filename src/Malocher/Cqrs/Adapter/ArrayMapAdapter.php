<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\Cqrs\Adapter;

use Malocher\Cqrs\Bus\BusInterface;

/**
 * Class ArrayMapAdapter
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Malocher\Cqrs\Adapter
 */
class ArrayMapAdapter implements AdapterInterface
{
    /**
     * @param array $configuration
     */
    public function __construct(array $configuration = null)
    {
        //empty constructor, just here to implement the interface
    }

    /**
     * Initialize (pipe) a bus via configuration file!
     *
     * @param BusInterface $bus
     * @param array $configuration
     * @throws AdapterException
     */
    public function pipe(BusInterface $bus, array $configuration)
    {
        foreach ($configuration as $messageClass => $callableOrDefinition) {
            if (!class_exists($messageClass)) {
                throw AdapterException::initializeError(sprintf('Message class <%s> does not exist', $messageClass));
            } else if ($this->isCommand($messageClass)) {
                $bus->mapCommand($messageClass, $callableOrDefinition);
            } else if ($this->isEvent($messageClass)) {
                $bus->registerEventListener($messageClass, $callableOrDefinition);
            } else if ($this->isQuery($messageClass)) {
                $bus->mapQuery($messageClass, $callableOrDefinition);
            } else {
                throw AdapterException::pipeError(sprintf(
                    'Message <%s> must implement %s, %s or %s',
                    $messageClass,
                    'Malocher\Cqrs\Command\CommandInterface',
                    'Malocher\Cqrs\Query\QueryInterface',
                    'Malocher\Cqrs\Event\EventInterface'
                ));
            }
        }
    }

    /**
     * Check if message implements Malocher\Cqrs\Command\CommandInterface
     *
     * @param string $messageClass
     * @return boolean
     */
    private function isCommand($messageClass)
    {
        $interfaces = class_implements($messageClass);
        if (!$interfaces) {
            return false;
        }
        return in_array('Malocher\Cqrs\Command\CommandInterface', $interfaces);
    }

    /**
     * Check if message implements Malocher\Cqrs\Query\QueryInterface
     *
     * @param string $messageClass
     * @return boolean
     */
    private function isQuery($messageClass)
    {
        $interfaces = class_implements($messageClass);
        if (!$interfaces) {
            return false;
        }
        return in_array('Malocher\Cqrs\Query\QueryInterface', $interfaces);
    }

    /**
     * Check if message implements Malocher\Cqrs\Command\CommandInterface
     *
     * @param string $messageClass
     * @return boolean
     */
    private function isEvent($messageClass)
    {
        $interfaces = class_implements($messageClass);
        if (!$interfaces) {
            return false;
        }
        return in_array('Malocher\Cqrs\Event\EventInterface', $interfaces);
    }
}
