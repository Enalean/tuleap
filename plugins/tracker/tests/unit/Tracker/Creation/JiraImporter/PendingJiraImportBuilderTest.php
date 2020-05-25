<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use ProjectManager;
use UserManager;

class PendingJiraImportBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var PendingJiraImportBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->project = Mockery::mock(Project::class);
        $this->user    = Mockery::mock(PFUser::class);

        $this->user_manager    = Mockery::mock(UserManager::class);
        $this->project_manager = Mockery::mock(ProjectManager::class);

        $this->builder = new PendingJiraImportBuilder($this->project_manager, $this->user_manager);
    }

    public function testItRaisesExceptionIfProjectIsNotValid(): void
    {
        $this->project->shouldReceive(
            [
                'isError'  => true,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItRaisesExceptionIfProjectIsNotActive(): void
    {
        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => false
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItRaisesExceptionIfUserIsNotAlive(): void
    {
        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => false,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItReturnsAPendingJiraImport(): void
    {
        $this->project->shouldReceive(
            [
                'isError'  => false,
                'isActive' => true
            ]
        );
        $this->project_manager->shouldReceive('getProject')->andReturn($this->project);

        $this->user->shouldReceive(
            [
                'isAlive' => true,
            ]
        );
        $this->user_manager->shouldReceive('getUserById')->andReturn($this->user);

        $pending_import = $this->builder->buildFromRow($this->aPendingImportRow());
        $this->assertEquals(12, $pending_import->getId());
    }

    private function aPendingImportRow(): array
    {
        return [
            'id'                   => 12,
            'created_on'           => 0,
            'jira_server'          => '',
            'jira_issue_type_name' => '',
            'tracker_name'         => '',
            'tracker_shortname'    => '',
            'project_id'           => 42,
            'user_id'              => 103,
            'jira_project_id'      => '',
            'jira_user_email'      => '',
            'encrypted_jira_token' => '',
            'tracker_color'        => '',
            'tracker_description'  => '',
        ];
    }
}
