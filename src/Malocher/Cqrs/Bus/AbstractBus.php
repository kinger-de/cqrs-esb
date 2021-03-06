<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\Cqrs\Bus;

use Malocher\Cqrs\Command\CommandHandlerLoaderInterface;
use Malocher\Cqrs\Command\CommandHandlerLoaderAwareInterface;
use Malocher\Cqrs\Command\CommandInterface;
use Malocher\Cqrs\Event\EventInterface;
use Malocher\Cqrs\Event\EventListenerLoaderInterface;
use Malocher\Cqrs\Event\EventListenerLoaderAwareInterface;
use Malocher\Cqrs\Gate;
use Malocher\Cqrs\Query\QueryHandlerLoaderInterface;
use Malocher\Cqrs\Query\QueryHandlerLoaderAwareInterface;
use Malocher\Cqrs\Query\QueryInterface;

/**
 * Class AbstractBus
 *
 * @author Alexander Miertsch <kontakt@codeliner.ws>
 * @package Malocher\Cqrs\Bus
 */
abstract class AbstractBus 
    implements BusInterface, 
    CommandHandlerLoaderAwareInterface, 
    QueryHandlerLoaderAwareInterface,
    EventListenerLoaderAwareInterface
{
    const SYSTEMBUS = 'system-bus';

    /**
     * @var \Malocher\Cqrs\Command\CommandHandlerLoaderInterface
     */
    protected $commandHandlerLoader;

    /**
     * @var \Malocher\Cqrs\Event\EventListenerLoaderInterface
     */
    protected $eventListenerLoader;

    /**
     *
     * @var \Malocher\Cqrs\Query\QueryHandlerLoaderInterface
     */
    protected $queryHandlerLoader;

    /**
     * @var array
     */
    protected $commandHandlerMap = array();

    /**
     * @var array
     */
    protected $eventListenerMap = array();

    /**
     * @var array
     */
    protected $queryHandlerMap = array();

    /**
     * @var Gate
     */
    protected $gate;

    /**
     * @param Gate $gate
     */
    public function setGate(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * @return Gate
     */
    public function getGate()
    {
        return $this->gate;
    }
    
    /**
     * 
     * @param CommandHandlerLoaderInterface $commandHandlerLoader
     */
    public function setCommandHandlerLoader(CommandHandlerLoaderInterface $commandHandlerLoader)
    {
        $this->commandHandlerLoader = $commandHandlerLoader;
    }
    
    /**
     * 
     * @param QueryHandlerLoaderInterface $queryHandlerLoader
     */
    public function setQueryHandlerLoader(QueryHandlerLoaderInterface $queryHandlerLoader)
    {
        $this->queryHandlerLoader = $queryHandlerLoader;
    }
    
    /**
     * 
     * @param EventListenerLoaderInterface $eventListenerLoader
     */
    public function setEventListenerLoader(EventListenerLoaderInterface $eventListenerLoader)
    {
        $this->eventListenerLoader = $eventListenerLoader;
    }

    /**
     * @param $commandClass
     * @param $callableOrDefinition
     * @return bool|mixed
     */
    public function mapCommand($commandClass, $callableOrDefinition)
    {
        if (!isset($this->commandHandlerMap[$commandClass])) {
            $this->commandHandlerMap[$commandClass] = array();
        }
        $this->commandHandlerMap[$commandClass][] = $callableOrDefinition;
        return true;
    }

    /**
     * @return array
     */
    public function getCommandHandlerMap()
    {
        return $this->commandHandlerMap;
    }

    /**
     * @param CommandInterface $command
     * @return bool|void
     * @throws BusException
     */
    public function invokeCommand(CommandInterface $command)
    {
        $commandClass = get_class($command);
        
        if (!isset($this->commandHandlerMap[$commandClass])) {
            return false;
        }

        foreach ($this->commandHandlerMap[$commandClass] as $callableOrDefinition) {

            if (is_callable($callableOrDefinition)) {
                call_user_func($callableOrDefinition, $command, $this->gate);
            }

            if (is_array($callableOrDefinition)) {
                if (is_null($this->commandHandlerLoader)) {
                    throw BusException::loaderNotExistError(
                        sprintf(
                            'Can not load the command handler <%s>. No CommandHandlerLoader found.',
                            $callableOrDefinition['alias']
                        )
                    );
                }
                $commandHandler = $this->commandHandlerLoader->getCommandHandler($callableOrDefinition['alias']);
                $method = $callableOrDefinition['method'];

                // instead of invoking the handler method directly
                // we call the execute function of the implemented trait and pass along a reference to the gate
                $usedTraits = class_uses($commandHandler);
                if (!isset($usedTraits['Malocher\Cqrs\Adapter\AdapterTrait'])) {
                    throw BusException::traitError('Adapter Trait is missing! Use it!');
                }
                $commandHandler->executeCommand($this, $commandHandler, $method, $command);
            }
        }

        return true;
    }

    /**
     * @param string $queryClass
     * @param mixed $callableOrDefinition
     * @return mixed
     */
    public function mapQuery($queryClass, $callableOrDefinition)
    {
        if (!isset($this->queryHandlerMap[$queryClass])) {
            $this->queryHandlerMap[$queryClass] = array();
        }
        $this->queryHandlerMap[$queryClass][] = $callableOrDefinition;
        return true;
    }

    /**
     * @return array
     */
    public function getQueryHandlerMap()
    {
        return $this->queryHandlerMap;
    }

    /**
     * Execute the query and return the result
     *
     * The bus loops over the QueryHandlerMap until a valid result is returned (not null)
     * by a handler or each handler has executed the query
     *
     * @param QueryInterface $query
     * @return mixed
     * @throws BusException
     */
    public function executeQuery(QueryInterface $query)
    {
        $queryClass = get_class($query);

        // Check if query exists after invoking the ExecuteQueryCommand because
        // the ExecuteQueryCommand tells that a query is executed but does not care
        // if it succeeded. Later the QueryExecutedEvent can be used to check if a
        // query succeeded.
        if (!isset($this->queryHandlerMap[$queryClass])) {
            return false;
        }

        $result = null;

        foreach ($this->queryHandlerMap[$queryClass] as $callableOrDefinition) {

            if (is_callable($callableOrDefinition)) {
                $result = call_user_func($callableOrDefinition, $query, $this->gate);

                if (!is_null($result)) {
                    break;
                }
            }

            if (is_array($callableOrDefinition)) {
                if (is_null($this->queryHandlerLoader)) {
                    throw BusException::loaderNotExistError(
                        sprintf(
                            'Can not load the query handler <%s>. No QueryHandlerLoader found.',
                            $callableOrDefinition['alias']
                        )
                    );
                }
                $queryHandler = $this->queryHandlerLoader->getQueryHandler($callableOrDefinition['alias']);
                $method = $callableOrDefinition['method'];

                // instead of invoking the handler method directly
                // we call the execute function of the implemented trait and pass along a reference to the gate
                $usedTraits = class_uses($queryHandler);
                if (!isset($usedTraits['Malocher\Cqrs\Adapter\AdapterTrait'])) {
                    throw BusException::traitError('Adapter Trait is missing! Use it!');
                }
                $result = $queryHandler->executeQuery($this, $queryHandler, $method, $query);
                if (!is_null($result)) {
                    break;
                }
            }
        }
        
        return $result;
    }

    /**
     * @param $eventClass
     * @param $callableOrDefinition
     * @return bool|mixed
     */
    public function registerEventListener($eventClass, $callableOrDefinition)
    {
        if (!isset($this->eventListenerMap[$eventClass])) {
            $this->eventListenerMap[$eventClass] = array();
        }
        $this->eventListenerMap[$eventClass][] = $callableOrDefinition;
        return true;
    }

    /**
     * @return array
     */
    public function getEventListenerMap()
    {
        return $this->eventListenerMap;
    }

    /**
     * @param EventInterface $event
     * @return bool|void
     * @throws BusException
     */
    public function publishEvent(EventInterface $event)
    {
        $eventClass = get_class($event);

        if (!isset($this->eventListenerMap[$eventClass])) {
            return false;
        }

        foreach ($this->eventListenerMap[$eventClass] as $callableOrDefinition) {
            if (is_callable($callableOrDefinition)) {
                call_user_func($callableOrDefinition, $event);
            }

            if (is_array($callableOrDefinition)) {
                if (is_null($this->eventListenerLoader)) {
                    throw BusException::loaderNotExistError(
                        sprintf(
                            'Can not load the event listener <%s>. No EventListenerLoader found.',
                            $callableOrDefinition['alias']
                        )
                    );
                }
                $eventListener = $this->eventListenerLoader->getEventListener($callableOrDefinition['alias']);
                $method = $callableOrDefinition['method'];

                // instead of invoking the handler method directly
                // we call the execute function of the implemented trait and pass along a reference to the gate
                $usedTraits = class_uses($eventListener);
                if (!isset($usedTraits['Malocher\Cqrs\Adapter\AdapterTrait'])) {
                    throw BusException::traitError('Adapter Trait is missing! Use it!');
                }
                $eventListener->executeEvent($this, $eventListener, $method, $event);
            }
        }

        return true;
    }
}
