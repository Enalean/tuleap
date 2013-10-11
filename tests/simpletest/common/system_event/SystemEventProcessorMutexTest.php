<?php

/*
 * Copyright Enalean (c) 2011, 2012, 2013. All rights reserved.
 * 
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

require_once 'common/system_event/SystemEventProcessorMutex.class.php';

class SystemEventProcessorMutex_ProcessingTest extends TuleapTestCase {
    private $mutex;
    private $object;

    public function setUp() {
        parent::setUp();
        $this->object = mock('IRunInAMutex');
        $this->process_manager = mock('SystemEventProcessManager');
        $this->mutex  = partial_mock(
            'SystemEventProcessorMutex',
            array('checkCurrentUserProcessOwner'),
            array($this->process_manager, $this->object)
        );
    }

    public function itChecksIfAlreadyRunning() {
        expect($this->process_manager)->isAlreadyRunning()->once();
        $this->mutex->execute();
    }

    public function itCreatesPidFile() {
        expect($this->process_manager)->createPidFile()->once();
        $this->mutex->execute();
    }

    public function itExecuteCallable() {
        expect($this->object)->execute()->once();
        $this->mutex->execute();
    }

    public function itDeletesPidFile() {
        expect($this->process_manager)->deletePidFile()->once();
        $this->mutex->execute();
    }

    public function itStopsIfAlreadyRunning() {
        stub($this->process_manager)->isAlreadyRunning()->returns(true);
        expect($this->process_manager)->createPidFile()->never();
        expect($this->object)->execute()->never();
        expect($this->process_manager)->deletePidFile()->never();
        $this->mutex->execute();
    }

    public function itStopsIfCurrentUserIsNotTheOneThatShouldRun() {
        $this->expectException();
        stub($this->mutex)->checkCurrentUserProcessOwner()->throws(new Exception('whatever'));
        expect($this->process_manager)->isAlreadyRunning()->never();
        expect($this->process_manager)->createPidFile()->never();
        expect($this->object)->execute()->never();
        expect($this->process_manager)->deletePidFile()->never();
        $this->mutex->execute();
    }
}
