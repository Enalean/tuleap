<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
 */

/**
 * Returns a DSL like mockgenerator
 *
 * <code>
 * stub('someclass')->someMethod($arg1, $arg2, ...)->returns($someResult);
 * </code>
 *
 * that is an alternative to :
 *
 * <code>
 * Mock::generate('SomeClass');
 * $mock = new MockSomeClass();
 * $mock->setReturnValue('someMethod', $someResult, array($arg1, $arg2, ...);
 * </code>
 *
 * @param a class name or a simpletest mock
 *
 * @return \OngoingIntelligentStub
 */
function stub($classname_or_simpletest_mock) {
    if (is_object($classname_or_simpletest_mock)) {
        $mock = $classname_or_simpletest_mock;
    } else {
        $mock = mock($classname_or_simpletest_mock);
    }
    return new OngoingIntelligentStub($mock);
}

function expect($classname_or_simpletest_mock) {
    return stub($classname_or_simpletest_mock);
}

/**
 * mock('SomeClass');
 *
 * is exactly the same as
 *
 * <code>
 * Mock::generate('SomeClass');
 * $mock = new MockSomeClass();
 * </code>
 *
 * @param type $classname
 *
 * @return a simpletest mock
 */
function mock($classname) {
    Mock::generate($classname);
    $mockclassname = "Mock$classname";
    return new $mockclassname();
}

function partial_stub($classname_or_simpletest_mock, array $mocked_methods) {
    if (is_object($classname_or_simpletest_mock)) {
        $mock = $classname_or_simpletest_mock;
    } else {
        $mock = partial_mock($classname_or_simpletest_mock);
    }
    return new OngoingIntelligentStub($mock);
}

function partial_mock($classname, array $mocked_methods, array $construct_params = null) {
    $object = TestHelper::getPartialMock($classname, $mocked_methods);
    if ($construct_params) {
        call_user_func_array(array($object, '__construct'), $construct_params);
    }
    return $object;
}

class OngoingIntelligentStub {
    public $mock;
    private $method;
    private $arguments;

    function __construct($mock) {
        $this->mock = $mock;
    }

    public function __call($name, $arguments) {
        if ($this->method) {
            throw new Exception("Cannot stub '{$name}()', method '{$this->method}()' already stubbed. Wrong usage of stub()");
        }

        $this->method    = $name;
        $this->arguments = $arguments;
        return $this;
    }

    public function once() {
        if (empty($this->arguments)) {
            $this->mock->expectOnce($this->method);
        } else {
            $this->mock->expectOnce($this->method, $this->arguments);
        }
        return $this;
    }

    public function never() {
        $this->mock->expectNever($this->method);
        return $this;
    }

    public function at($timing) {
        $this->mock->expectAt($timing, $this->method, $this->arguments);
        return $this;
    }

    public function count($count) {
        $this->mock->expectCallCount($this->method, $count);
        return $this;
    }

    /**
     * @return the configured mock
     */
    public function returns($value) {
        if (empty($this->arguments)) {
            $this->mock->setReturnValue($this->method, $value);
        } else {
            $this->mock->setReturnValue($this->method, $value, $this->arguments);
        }
        return $this->mock;
    }

    /**
     * @return the configured mock
     */
    public function returnsAt($timing, $value) {
        $this->mock->setReturnValueAt($timing, $this->method, $value);
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
    public function returnsDar() {
        return $this->returns(TestHelper::argListToDar(func_get_args()));
    }

    /**
     * Ease returns of empty DatabaseAccessResult
     *
     * Example:
     * stub('Dao')->getStuff()->returnsEmptyDar()
     */
    public function returnsEmptyDar() {
        return $this->returns(TestHelper::emptyDar());
    }

    /**
     * Ease returns of DatabaseAccessResult with errors
     *
     * Example:
     * stub('Dao')->getStuff()->returnsDarWithErrors()
     */
    public function returnsDarWithErrors() {
        return $this->returns(TestHelper::errorDar());
    }

    public function throws(Exception $e) {
        if (empty($this->arguments)) {
            $this->mock->throwOn($this->method, $e);
        } else {
            $this->mock->throwOn($this->method, $e, $this->arguments);
        }
        return $this->mock;
    }

    public function throwsAt($timing, Exception $e) {
        if (empty($this->arguments)) {
            $this->mock->throwAt($timing, $this->method, $e);
        } else {
            $this->mock->throwAt($timing, $this->method, $e, $this->arguments);
        }
        return $this->mock;
    }
}
?>
