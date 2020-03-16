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
function stub($classname_or_simpletest_mock)
{
    if ($classname_or_simpletest_mock instanceof \Mockery\MockInterface) {
        return new Tuleap\Test\MockeryOngoingIntelligentStub($classname_or_simpletest_mock);
    } else {
        if (is_object($classname_or_simpletest_mock)) {
            $mock = $classname_or_simpletest_mock;
        } else {
            $mock = mock($classname_or_simpletest_mock);
        }
        return new SimpleMockOngoingInterlligentStub($mock);
    }
}

/**
 * Allow stubbing with string
 *
 * mockery_stub(PFUser::class)->getId()->returns(121);
 *
 * It should be used only for conversion of old SimpleTest to Mockery tests in order to only change calls like
 *
 *     stub('PFUser')->getId()->returns(121);
 *
 * into
 *
 *     mockery_stub(PFUser::class)->getId()->returns(121);
 *
 * @param $classname_or_simpletest_mock
 * @return null|OngoingIntelligentStub
 */
function mockery_stub($classname_or_simpletest_mock)
{
    if (is_string($classname_or_simpletest_mock)) {
        return stub(\Mockery::spy($classname_or_simpletest_mock));
    }
    return null;
}

function expect($classname_or_simpletest_mock)
{
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
function mock($classname)
{
    $mockclassname = "Mock$classname";
    if (strpos($classname, '\\') !== false) {
        $mockclassname = "Mock" . str_replace('\\', '_', $classname);
    }
    Mock::generate($classname, $mockclassname);
    return new $mockclassname();
}

function safe_mock($classname)
{
    return \Mockery::mock($classname);
}

function partial_stub($classname_or_simpletest_mock, array $mocked_methods)
{
    if (is_object($classname_or_simpletest_mock)) {
        $mock = $classname_or_simpletest_mock;
    } else {
        $mock = partial_mock($classname_or_simpletest_mock);
    }
    return new OngoingIntelligentStub($mock);
}

function partial_mock($classname, array $mocked_methods, ?array $construct_params = null)
{
    $object = TestHelper::getPartialMock($classname, $mocked_methods);
    if ($construct_params) {
        call_user_func_array(array($object, '__construct'), $construct_params);
    }
    return $object;
}
