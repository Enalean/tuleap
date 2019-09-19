<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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

class WrapperLoggerTest extends TuleapTestCase
{

    private $logger;

    public function setUp()
    {
        parent::setUp();
        $this->logger = mock('Logger');
    }

    public function itAppendAPrefix()
    {
        $wrapper = new WrapperLogger($this->logger, 'stuff');

        expect($this->logger)->info('[stuff] bla')->once();

        $wrapper->info('bla');
    }

    public function itWrapAWrapper()
    {
        $wrapper1 = new WrapperLogger($this->logger, 'tracker');

        $wrapper2 = new WrapperLogger($wrapper1, 'artifact');

        expect($this->logger)->info('[tracker] [artifact] bla')->once();

        $wrapper2->info('bla');
    }

    public function itAddAPrefixDynamically()
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        expect($this->logger)->info('[tracker][53] bla')->once();

        $wrapper->push('53');
        $wrapper->info('bla');
    }

    public function itAddAPrefixDynamicallyAndItsKept()
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        expect($this->logger)->info()->count(2);
        expect($this->logger)->info('[tracker][53] bla')->at(0);
        expect($this->logger)->info('[tracker][53] coin')->at(1);

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->info('coin');
    }

    public function testAddedPrefixAreStacked()
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        expect($this->logger)->info()->count(2);
        expect($this->logger)->info('[tracker][53] bla')->at(0);
        expect($this->logger)->info('[tracker][53][field] coin')->at(1);

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->push('field');
        $wrapper->info('coin');
    }

    public function itPopPrefixes()
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        expect($this->logger)->info()->count(2);
        expect($this->logger)->info('[tracker][53] bla')->at(0);
        expect($this->logger)->info('[tracker] coin')->at(1);

        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->pop();
        $wrapper->info('coin');
    }

    public function itPopPrefixes2()
    {
        $wrapper = new WrapperLogger($this->logger, 'tracker');

        expect($this->logger)->info()->count(3);
        expect($this->logger)->info('[tracker] stuff')->at(0);
        expect($this->logger)->info('[tracker][53] bla')->at(1);
        expect($this->logger)->info('[tracker][54] coin')->at(2);

        $wrapper->info('stuff');
        $wrapper->push('53');
        $wrapper->info('bla');
        $wrapper->pop();
        $wrapper->push('54');
        $wrapper->info('coin');
    }
}
