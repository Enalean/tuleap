<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\SystemEvent;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use SystemEventProcessManager;

class SystemEventProcessManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $fixtures_dir;
    private $fixture_file;
    /**
     * @var SystemEventProcessManager
     */
    private $process_manager;
    /**
     * @var \Mockery\MockInterface|\SystemEventProcess
     */
    private $process;
    private $root;

    public function setUp() : void
    {
        $this->root         = vfsStream::setup('slash');
        $this->fixtures_dir = $this->root->url();
        $this->fixture_file = $this->fixtures_dir.'/tuleap_process_system_event.pid';

        $this->process = \Mockery::mock(\SystemEventProcess::class, ['getPidFile' => $this->fixture_file]);

        $this->process_manager = new SystemEventProcessManager();
    }

    public function testItWritesPidFileOnStart()
    {
        $this->assertFileNotExists($this->fixture_file);

        $this->process_manager->createPidFile($this->process);

        $this->assertFileExists($this->fixture_file);
    }

    public function testItWritesProcessPid()
    {
        $this->process_manager->createPidFile($this->process);

        $this->assertStringEqualsFile($this->fixture_file, (string) getmypid());
    }

    public function testItThrowAnExceptionWhenCannotWritePidFile()
    {
        vfsStream::newFile('stuff.pid', 0000)->at($this->root);

        $this->process->shouldReceive('getPidFile')->andReturns($this->root->url() . '/stuff.pid');

        $this->expectException(\Exception::class);

        $this->process_manager->createPidFile($this->process);
    }

    public function itRemovesPidFileOnEnd()
    {
        $this->process_manager->createPidFile($this->process);
        $this->process_manager->deletePidFile($this->process);

        $this->assertFileNotExists($this->fixture_file);
    }
}
