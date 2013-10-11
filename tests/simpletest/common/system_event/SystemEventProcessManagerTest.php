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

require_once 'common/system_event/SystemEventProcessManager.class.php';

class SystemEventProcessManagerTest extends TuleapTestCase {

    private $fixtures_dir;
    private $fixture_file;

    public function setUp() {
        parent::setUp();
        $this->fixtures_dir = dirname(__FILE__).'/_fixtures';
        $this->fixture_file = $this->fixtures_dir.'/tuleap_process_system_event.pid';
        mkdir($this->fixtures_dir);

        $this->process = stub('SystemEventProcess')->getPidFile()->returns($this->fixture_file);

        $this->process_manager = new SystemEventProcessManager();
    }

    public function tearDown() {
        parent::tearDown();
        $this->recurseDeleteInDir($this->fixtures_dir);
        rmdir($this->fixtures_dir);
    }

    public function itWritesPidFileOnStart() {
        $this->assertFileDoesntExist($this->fixture_file);

        $this->process_manager->createPidFile($this->process);

        $this->assertFileExists($this->fixture_file);
    }

    public function itWritesProcessPid() {
        $this->process_manager->createPidFile($this->process);

        $this->assertEqual(file_get_contents($this->fixture_file), getmypid());
    }

    public function itThrowAnExceptionWhenCannotWritePidFile() {
        $process = stub('SystemEventProcess')->getPidFile()->returns('/root');

        $this->expectException();

        $this->process_manager->createPidFile($process);
    }

    public function itRemovesPidFileOnEnd() {
        $this->process_manager->createPidFile($this->process);
        $this->process_manager->deletePidFile($this->process);

        $this->assertFileDoesntExist($this->fixture_file);
    }
}
