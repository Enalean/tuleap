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
 */

namespace Tuleap\Test;

class FooBar
{
    public function add($arg1, $arg2)
    {
        return null;
    }

    public function getRow()
    {
        return ['a'];
    }
}

class MockeryExamplesTest extends \TuleapTestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MultipleClasses
{
    public function itMatchArgumentAndReturnValue()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3);

        $this->assertEqual($foo_bar->add(1, 2), 3);
    }

    public function itRaisesAnErrorWhenArgumentsDoesntMatch()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3);

        $this->expectException(\Mockery\Exception\NoMatchingExpectationException::class);
        $foo_bar->add(1, 3);
    }

    public function itMocksSeveralCalls()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3);
        $foo_bar->shouldReceive('add')->with(4, 6)->andReturns(10);

        $this->assertEqual($foo_bar->add(1, 2), 3);
        $this->assertEqual($foo_bar->add(4, 6), 10);
    }

    public function itMocksSeveralCallsRegardlessOfOrder()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3);
        $foo_bar->shouldReceive('add')->with(4, 6)->andReturns(10);

        $this->assertEqual($foo_bar->add(4, 6), 10);
        $this->assertEqual($foo_bar->add(1, 2), 3);
    }

    public function itRaisesAnErrorWhenOrderIsNotMatched()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3)->ordered();
        $foo_bar->shouldReceive('add')->with(4, 6)->andReturns(10)->ordered();

        $this->expectException(\Mockery\Exception\InvalidOrderException::class);
        $this->assertEqual($foo_bar->add(4, 6), 10);
        $this->assertEqual($foo_bar->add(1, 2), 3);
    }

    public function itReturnsTheRightValueWhenOrderedIsMatched()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->with(1, 2)->andReturns(3)->ordered();
        $foo_bar->shouldReceive('add')->with(4, 6)->andReturns(10)->ordered();

        $this->assertEqual($foo_bar->add(1, 2), 3);
        $this->assertEqual($foo_bar->add(4, 6), 10);
    }

    public function itItFailsToReturnsTheRightValueWhenOrderedIsMatchedWithoutArgsUsingOrdered()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->andReturns(3)->ordered();
        $foo_bar->shouldReceive('add')->andReturns(10)->ordered();

        $this->assertEqual($foo_bar->add(1, 2), 3);
        $this->assertEqual($foo_bar->add(4, 6), 3);
    }


    public function itReturnsTheRightValueWhenOrderedIsMatchedWithoutArgsUsingAllows()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->allows()->add(1, 2)->andReturns(3)->ordered();
        $foo_bar->allows()->add(4, 6)->andReturns(10)->ordered();


        $this->assertEqual($foo_bar->add(1, 2), 3);
        $this->assertEqual($foo_bar->add(4, 6), 10);
    }

    public function itReturnsTheRightValueWhenOrderedIsMatchedWithoutArgs()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->once()->andReturns(3);
        $foo_bar->shouldReceive('add')->once()->andReturns(10);
        $foo_bar->shouldReceive('add')->once()->andReturns(20);

        $this->assertEqual($foo_bar->add(1, 2), 3);
        $this->assertEqual($foo_bar->add(4, 6), 10);
        $this->assertEqual($foo_bar->add(4, 6), 20);
    }

    public function itAcceptsParametersInAnyOrder()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('add')->andReturns(3)->once()->with(1, 2);

        $this->assertEqual($foo_bar->add(1, 2), 3);
    }


    public function itStuff()
    {
        $foo_bar = \Mockery::mock(FooBar::class);

        $foo_bar->shouldReceive('getRow')->once()->andReturns(3);
        $foo_bar->shouldReceive('getRow')->once()->andReturns(4);
        $foo_bar->shouldReceive('getRow')->once()->andReturns(false);

        $this->assertEqual($foo_bar->getRow(), 3);
        $this->assertEqual($foo_bar->getRow(), 4);
        $this->assertEqual($foo_bar->getRow(), false);
    }
}
