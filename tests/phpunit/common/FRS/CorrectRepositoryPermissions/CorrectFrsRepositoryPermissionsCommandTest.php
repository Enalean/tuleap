<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\FRS;

use DirectoryIterator;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class CorrectFrsRepositoryPermissionsCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var DirectoryIterator
     */
    private $directory;

    /**
     * @var \ProjectManager|\Mockery\MockInterface
     */
    private $project_manager;

    /**
     * @var CorrectFrsRepositoryPermissionsCommand
     */
    private $correct_command;

    /**
     * @var string
     */
    private $base;

    /**
     * @var \Project|\Mockery\MockInterface
     */
    private $project_1;

    /**
     * @var \Project|\Mockery\MockInterface
     */
    private $project_2;

    /**
     * @var \Project|\Mockery\MockInterface
     */
    private $project_3;

    protected function setUp(): void
    {
        vfsStream::setup('slash');
        $this->base            = vfsStream::url('slash');
        $this->project_manager = \Mockery::mock(\ProjectManager::class);

        $this->initFiles();

        $this->directory       = new DirectoryIterator($this->base . '/tuleap/ftp/tuleap/');
        $this->correct_command = new CorrectFrsRepositoryPermissionsCommand(
            $this->directory,
            $this->project_manager
        );
    }

    private function initFiles(): void
    {
        mkdir($this->base . '/tuleap/ftp/tuleap/', 0777, true);
        mkdir($this->base . '/tuleap/ftp/tuleap/leprojet', 0777, true);
        mkdir($this->base . '/tuleap/ftp/tuleap/leprojet2', 0777, true);
        mkdir($this->base . '/tuleap/ftp/tuleap/leprojet3', 0777, true);
        mkdir($this->base . '/tuleap/ftp/tuleap/DELETED', 0777, true);

        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet', 0);
        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet2', 0);
        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet3', 0);
        chgrp($this->base . '/tuleap/ftp/tuleap/DELETED', 496);
    }

    private function initProjects(): void
    {
        $this->project_1 = \Mockery::mock(\Project::class);
        $this->project_2 = \Mockery::mock(\Project::class);
        $this->project_3 = \Mockery::mock(\Project::class);

        $this->project_1->shouldReceive('getUnixGID')->andReturn(1);
        $this->project_2->shouldReceive('getUnixGID')->andReturn(2);

        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet'])->andReturn(
            $this->project_1
        );
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet2'])->andReturn(
            $this->project_2
        );
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet3'])->andReturn(
            $this->project_3
        );
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['DELETED'])->andReturn(null);
    }

    public function testChangeGroupSuccess()
    {
        $this->initProjects();

        $this->project_3->shouldReceive('getUnixGID')->andReturn(3);

        $command_tester = new CommandTester($this->correct_command);
        $command_tester->execute([]);

        $i = 1;
        foreach ($this->directory as $file) {
            if ($file->isDot() || $file->getFilename() === "DELETED") {
                continue;
            }

            $this->assertEquals($file->getGroup(), $i);
            $i++;
        }

        $text_table = $command_tester->getDisplay();

        $this->assertStringContainsString("Project permissions of leprojet has been changed.", $text_table);
        $this->assertStringContainsString("Project permissions of leprojet2 has been changed.", $text_table);
        $this->assertStringContainsString("Project permissions of leprojet3 has been changed.", $text_table);
        $this->assertStringContainsString("3 permissions has been changed.", $text_table);
    }

    public function testNoChangeGroupWhenTheirAreCorrectlySet()
    {
        $this->initProjects();

        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet', 1);
        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet2', 2);
        chgrp($this->base . '/tuleap/ftp/tuleap/leprojet3', 3);

        $this->project_3->shouldReceive('getUnixGID')->andReturn(3);

        $command_tester = new CommandTester($this->correct_command);
        $command_tester->execute([]);

        $i = 1;
        foreach ($this->directory as $file) {
            if ($file->isDot() || $file->getFilename() === "DELETED") {
                continue;
            }

            $this->assertEquals($file->getGroup(), $i);
            $i++;
        }

        $text_table = $command_tester->getDisplay();

        $this->assertStringContainsString("No permissions has been changed.", $text_table);
    }

    public function testChangeGroupWithOneWrongGroup()
    {
        $this->initProjects();

        $this->project_3->shouldReceive('getUnixGID')->andReturn('wrong_perm');

        $command_tester = new CommandTester($this->correct_command);
        $command_tester->execute([]);

        $text_table = $command_tester->getDisplay();

        $this->assertStringContainsString("Project permissions of leprojet has been changed.", $text_table);
        $this->assertStringContainsString("Project permissions of leprojet2 has been changed.", $text_table);
        $this->assertStringContainsString("Wrong permissions of leprojet3 has not been changed.", $text_table);
        $this->assertStringContainsString("2 permissions has been changed.", $text_table);
    }

    public function testChangeGroupWithDeleted()
    {
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet'])->andReturn(null);
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet2'])->andReturn(null);
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['leprojet3'])->andReturn(null);
        $this->project_manager->shouldReceive('getProjectByUnixName')->withArgs(['DELETED'])->andReturn(null);

        chgrp($this->base . '/tuleap/ftp/tuleap/DELETED', 0);

        $command_tester = new CommandTester($this->correct_command);
        $command_tester->execute([]);

        $text_table = $command_tester->getDisplay();

        $this->assertStringContainsString("Project permissions of DELETED has been changed.", $text_table);
        $this->assertStringContainsString("1 permissions has been changed.", $text_table);
    }
}
