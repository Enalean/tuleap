<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Test;

use Mockery\ExpectationInterface;
use Mockery\HigherOrderMessage;
use Mockery\MockInterface;

/**
 * This class is more or less a replacement for SimpleMockOngoingInterlligentStub while not sharing the same interface
 *
 * As SimpleTest and Mockery have different ways to handle mocking we cannot share the exact same signature. The methods
 * that are not present here are meant to make the calling test crash to rewrite it the Mockery way.
 *
 * This class was introduced to ease the conversion from SimpleTest mocks to Mockery by not having to rewrite all tests
 * that depends on Tuleap test abstractions.
 *
 * @package Tuleap\Test
 */
class MockeryOngoingIntelligentStub
{
    /**
     * @var MockInterface
     */
    private $mock;
    private $method;
    private $arguments;
    /**
     * @var ExpectationInterface|HigherOrderMessage
     */
    private $shouldReceive;

    public function __construct(MockInterface $mock)
    {
        $this->mock = $mock;
    }

    public function __call($name, $arguments)
    {
        if ($this->method) {
            throw new \Exception("Cannot stub '{$name}()', method '{$this->method}()' already stubbed. Wrong usage of stub()");
        }

        $this->method    = $name;
        foreach ($arguments as $arg) {
            if ($arg === '*') {
                $this->arguments[] = \Mockery::any();
            } else {
                $this->arguments[] = $arg;
            }
        }

        $this->setShouldReceive();

        return $this;
    }

    private function setShouldReceive()
    {
        if (empty($this->arguments)) {
            $this->shouldReceive = $this->mock->shouldReceive($this->method);
        } else {
            $this->shouldReceive = $this->mock->shouldReceive($this->method)->withArgs($this->arguments);
        }
        return $this->shouldReceive;
    }

    public function once()
    {
        $this->shouldReceive->once();
        return $this;
    }

    public function atLeastOnce()
    {
        $this->shouldReceive->atLeast(1);
        return $this;
    }

    public function never()
    {
        $this->shouldReceive->never();
        return $this;
    }

    public function count($count)
    {
        $this->shouldReceive->times($count);
        return $this;
    }

    public function returns($value)
    {
        $this->shouldReceive->andReturns($value);

        return $this->mock;
    }

    public function throws(\Exception $e)
    {
        $this->shouldReceive->andThrow($e);
        return $this->mock;
    }

    /**
     * Ease return of DatabaseAccessResult objects:
     *
     * Example:
     * stub('Dao')->getStuff()->returnsDar(array('id' => '1'), array('id' => '2'));
     *
     * Returns 2 rows out of the database:
     * |Id|
     * |1 |
     * |2 |
     */
    public function returnsDar()
    {
        return $this->returnsDarFromArray(func_get_args());
    }

    /**
     * Ease return of DatabaseAccessResult objects:
     *
     * Example:
     *  stub('Dao')->getStuff()->returnsDarFromArray(
     *      array(
     *          array('id' => '1'),
     *          array('id' => '2')
     *      )
     *  );
     *
     * Returns 2 rows out of the database:
     * |Id|
     * |1 |
     * |2 |
     */
    public function returnsDarFromArray($array)
    {
        return $this->returns(\TestHelper::argListToDar($array));
    }

    /**
     * Ease returns of empty DatabaseAccessResult
     *
     * Example:
     * stub('Dao')->getStuff()->returnsEmptyDar()
     */
    public function returnsEmptyDar()
    {
        return $this->returns(\TestHelper::emptyDar());
    }

    /**
     * Ease returns of DatabaseAccessResult with errors
     *
     * Example:
     * stub('Dao')->getStuff()->returnsDarWithErrors()
     */
    public function returnsDarWithErrors()
    {
        return $this->returns(\TestHelper::errorDar());
    }

    public function returnsAt($timing, $value)
    {
        throw new \Exception("returnsAt not supported, you should rewrite the test using with()->once() or ordered()");
    }

    public function throwsAt($timing, \Exception $e)
    {
        throw new \Exception("throwsAt not supported, you should rewrite the test using with()->once() or ordered()");
    }
}
