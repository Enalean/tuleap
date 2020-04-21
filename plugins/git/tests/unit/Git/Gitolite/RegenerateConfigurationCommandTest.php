<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Git\Gitolite;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

class RegenerateConfigurationCommandTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $project_manager;
    private $event_manager;

    protected function setUp(): void
    {
        $this->project_manager = \Mockery::mock(\ProjectManager::class);
        $this->event_manager   = \Mockery::mock(\Git_SystemEventManager::class);
    }

    public function testConfigurationForAllProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $project_1 = \Mockery::mock(\Project::class);
        $project_1->shouldReceive('getID')->andReturns('999');
        $project_2 = \Mockery::mock(\Project::class);
        $project_2->shouldReceive('getID')->andReturns('888');
        $this->project_manager->shouldReceive('getProjectsByStatus')->with(\Project::STATUS_ACTIVE)->andReturns([$project_1, $project_2]);
        $this->event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with(['999', '888'])->once();

        $command_tester->execute(['--all' => true, 'project_ids' => ['102', '103']]);
        $this->assertSame(0, $command_tester->getStatusCode());
    }

    public function testConfigurationForSomeProjectsCanBeRegenerated(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $project_1 = \Mockery::mock(\Project::class);
        $project_1->shouldReceive('getID')->andReturns('102');
        $project_1->shouldReceive('isActive')->andReturns(true);
        $project_2 = \Mockery::mock(\Project::class);
        $project_2->shouldReceive('getID')->andReturns('103');
        $project_2->shouldReceive('isActive')->andReturns(true);
        $project_3 = \Mockery::mock(\Project::class);
        $project_3->shouldReceive('getID')->andReturns('104');
        $project_3->shouldReceive('isActive')->andReturns(false);
        $this->project_manager->shouldReceive('getValidProject')->with('102')->andReturns($project_1);
        $this->project_manager->shouldReceive('getValidProject')->with('103')->andReturns($project_2);
        $this->project_manager->shouldReceive('getValidProject')->with('104')->andReturns($project_3);
        $this->event_manager->shouldReceive('queueProjectsConfigurationUpdate')->with(['102', '103'])->once();

        $command_tester->execute(['project_ids' => ['102', '103']]);
        $this->assertSame(0, $command_tester->getStatusCode());
    }

    public function testInvalidProjectIDIsRejected(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $this->project_manager->shouldReceive('getValidProject')->andThrows(\Project_NotFoundException::class);
        $this->event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $command_tester->execute(['project_ids' => ['999999999999999999', '103']]);
        $this->assertSame(1, $command_tester->getStatusCode());
    }

    public function testNoUnnecessaryWorkIsDoneWhenNoProjectIDIsProvided(): void
    {
        $command        = new RegenerateConfigurationCommand($this->project_manager, $this->event_manager);
        $command_tester = new CommandTester($command);

        $this->event_manager->shouldReceive('queueProjectsConfigurationUpdate')->never();

        $command_tester->execute(['project_ids' => []]);
        $this->assertSame(0, $command_tester->getStatusCode());
    }
}
