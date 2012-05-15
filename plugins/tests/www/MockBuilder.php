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
    Mock::generate($classname);
    $mockclassname = "Mock$classname";
    return new $mockclassname();
}

/**
 * Setup both an expectation and a stub.
 * 
 * <code>
 *   expect($mock)->foo('bar', 'baz')->returns('qux');
 * </code>
 * 
 * is exactly the same as
 * 
 * <code>
 *   $mock->expectOnce('foo', array('bar', 'baz'));
 *   $mock->setReturnValue('foo', 'qux');
 * </code>
 * 
 * Setting return value is not mandatory:
 * 
 * <code>
 *   expect($mock)->foo('bar', 'baz');
 * </code>
 * 
 * is exactly the same as
 * 
 * <code>
 *   $mock->expectOnce('foo', array('bar', 'baz'));
 * </code>
 * 
 * TODO:
 *   - Support other expectations, not only "expectOnce"
 * 
 * @param mixed $mock A mock instance
 * 
 * @return \OngoingIntelligentStub 
 */
function expect($mock) {
    return new OngoingIntelligentStub($mock, 'expectOnce');
}

class OngoingIntelligentStub {

    function __construct($mock, $expectation = false) {
        $this->mock        = $mock;
        $this->expectation = $expectation;
    }

    public function __call($name, $arguments) {
        $this->method = $name;
        $this->arguments = $arguments;
        
        if ($this->expectation) {
            $expectation = $this->expectation;
            $this->mock->$expectation($this->method, $this->arguments);
        }
        
        return $this;
    }

    /**
     * @return the configured mock 
     */
    public function returns($value) {
        if (empty($this->arguments) || $this->expectation) {
            $this->mock->setReturnValue($this->method, $value);
        } else {
            $this->mock->setReturnValue($this->method, $value, $this->arguments);
        }
        
        return $this->mock;
    }
    

}
?>
