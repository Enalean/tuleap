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

class SystemEventProcessorMutex4Tests extends SystemEventProcessorMutex {
    public function createPidFile() {
        parent::createPidFile();
    }
    public function deletePidFile() {
        parent::deletePidFile();
    }
}


class SystemEventProcessorMutex_ProcessingTest extends TuleapTestCase {
    private $mutex;
    private $object;

    public function setUp() {
        parent::setUp();
        $this->object = mock('IRunInAMutex');
        $this->mutex  = partial_mock(
            'SystemEventProcessorMutex',
            array('checkCurrentUserProcessOwner', 'createPidFile', 'deletePidFile', 'isAlreadyRunning'),
            array($this->object)
        );
    }

    public function itChecksIfAlreadyRunning() {
        expect($this->mutex)->isAlreadyRunning()->once();
        $this->mutex->execute();
    }

    public function itCreatesPidFile() {
        expect($this->mutex)->createPidFile()->once();
        $this->mutex->execute();
    }

    public function itExecuteCallable() {
        expect($this->object)->execute()->once();
        $this->mutex->execute();
    }

    public function itDeletesPidFile() {
        expect($this->mutex)->deletePidFile()->once();
        $this->mutex->execute();
    }

    public function itStopsIfAlreadyRunning() {
        stub($this->mutex)->isAlreadyRunning()->returns(true);
        expect($this->mutex)->createPidFile()->never();
        expect($this->object)->execute()->never();
        expect($this->mutex)->deletePidFile()->never();
        $this->mutex->execute();
    }

    public function itStopsIfCurrentUserIsNotTheOneThatShouldRun() {
        $this->expectException();
        stub($this->mutex)->checkCurrentUserProcessOwner()->throws(new Exception('whatever'));
        expect($this->mutex)->isAlreadyRunning()->never();
        expect($this->mutex)->createPidFile()->never();
        expect($this->object)->execute()->never();
        expect($this->mutex)->deletePidFile()->never();
        $this->mutex->execute();
    }
}

class SystemEventProcessorMutex_FileTest extends TuleapTestCase {
    private $mutex;
    private $fixtures_dir;
    private $fixture_file;

    public function setUp() {
        parent::setUp();
        $this->fixtures_dir = dirname(__FILE__).'/_fixtures';
        mkdir($this->fixtures_dir);
        $this->fixture_file = $this->fixtures_dir.'/tuleap_process_system_event.pid';
        $processor = stub('IRunInAMutex')->getPidFilePath()->returns($this->fixture_file);
        $this->mutex = new SystemEventProcessorMutex4Tests($processor);
    }

    public function tearDown() {
        parent::tearDown();
        $this->recurseDeleteInDir($this->fixtures_dir);
        rmdir($this->fixtures_dir);
    }

    public function itWritesPidFileOnStart() {
        $this->assertFileDoesntExist($this->fixtures_dir.'/tuleap_process_system_event.pid');
        $this->mutex->createPidFile();
        $this->assertFileExists($this->fixtures_dir.'/tuleap_process_system_event.pid');
    }

    public function itWritesProcessPid() {
        $this->mutex->createPidFile();
        $this->assertEqual(file_get_contents($this->fixtures_dir.'/tuleap_process_system_event.pid'), getmypid());
    }

    public function itThrowAnExceptionWhenCannotWritePidFile() {
        $this->mutex = new SystemEventProcessorMutex4Tests(stub('IRunInAMutex')->getPidFilePath()->returns('/root'));
        $this->expectException();
        $this->mutex->createPidFile();
    }

    public function itRemovesPidFileOnEnd() {
        $this->mutex->createPidFile();
        $this->mutex->deletePidFile();
        $this->assertFileDoesntExist($this->fixtures_dir.'/tuleap_process_system_event.pid');

    }
}

?>
