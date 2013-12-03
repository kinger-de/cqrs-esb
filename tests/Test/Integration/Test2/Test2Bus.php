<?php
/*
 * This file is part of the Cqrs package.
 * (c) Manfred Weber <crafics@php.net> and Alexander Miertsch <kontakt@codeliner.ws>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace Test\Integration\Test2;

use Malocher\Cqrs\Bus\AbstractBus;

/**
 * Class Test2Bus
 *
 * @author Manfred Weber <crafics@php.net>
 * @package Test\Integration\Test2
 */
class Test2Bus extends AbstractBus
{
    /**
     * @return string
     */
    public function getName()
    {
        return 'test-integration-test2-bus';
    }
}
