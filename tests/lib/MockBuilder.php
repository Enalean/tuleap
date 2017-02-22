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
 * @param string $classname
 *
 * @return a simpletest mock
 */
function mock($classname) {
    $mockclassname = "Mock$classname";
    if (strpos($classname, '\\') !== false) {
        $mockclassname = "Mock". str_replace('\\', '_', $classname);
    }
    Mock::generate($classname, $mockclassname);
    return new $mockclassname();
}

class UnimplementedThrower {
    private $class;
    private $method;

    public function __construct($class, $method) {
        $this->class = $class;
        $this->method = $method;
    }

    public function act() {
        throw new Exception("Unimplemented {$this->class}->{$this->method}(...)");
    }
}

class SignatureMapWithDefault extends SimpleSignatureMap {

    private $default_action;

    public function __construct($default_action) {
        parent::SimpleSignatureMap();
        $this->default_action = $default_action;
    }

    public function &findFirstAction($parameters) {
        $slot = $this->_findFirstSlot($parameters);
        if (isset($slot) && isset($slot['content'])) {
            return $slot['content'];
        }
        return $this->default_action;
    }
}

function mock_safe_init($mock) {
    $class = get_class($mock);
    $methods = isset($mock->_mocked_methods) ? $mock->_mocked_methods : get_class_methods($class);
    foreach($methods as $method) {
        if(!method_exists($mock, $method)) continue;
        if( (($mm = (array) $mock) && isset($mm['_actions'])) ||
            (isset($mock->_mock) && ($mm = (array) $mock->_mock) && isset($mm['_actions'])))
        {
            $mn = strtolower($method);
            $mm['_actions']->_always[$mn] = new SignatureMapWithDefault(new UnimplementedThrower($class, $method));
        } else {
            @$mock->throwOn($method, new Exception("Unimplemented $class->$method(...)"));
        }
    }
    return $mock;
}

function safe_mock($classname) {
    return mock_safe_init(mock($classname));
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

function safe_partial_mock($classname) {
    return mock_safe_init(partial_mock($classname));
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

    public function once($message = '%s') {
        if (empty($this->arguments)) {
            $this->mock->expectOnce($this->method, false, $message);
        } else {
            $this->mock->expectOnce($this->method, $this->arguments, $message);
        }
        return $this;
    }

    public function atLeastOnce($message = '%s') {
        if (empty($this->arguments)) {
            $this->mock->expectAtLeastOnce($this->method, false, $message);
        } else {
            $this->mock->expectAtLeastOnce($this->method, $this->arguments, $message);
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
    public function returnsDarFromArray($array) {
        return $this->returns(TestHelper::argListToDar($array));
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
