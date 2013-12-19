<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Malocher\CqrsTest\Coverage\Mock\Event;

use Malocher\Cqrs\Adapter\AdapterTrait;

/**
 * Class MockCallbackEventHandler
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Malocher\CqrsTest\Coverage\Mock\Event
 */
class MockCallbackEventHandler
{
    use AdapterTrait;

    /**
     * @param MockEvent $event
     */
    public function handleEvent(MockEvent $event)
    {
        if ($event instanceof MockEvent) {
            $event->edit();
        }
    }

    /**
     * @event Malocher\CqrsTest\Coverage\Mock\Event\MockEvent
     * @param MockEvent $event
     */
    public function handleAnnotationEvent(MockEvent $event)
    {
        if ($event instanceof MockEvent) {
            $event->edit();
        }
    }
}
