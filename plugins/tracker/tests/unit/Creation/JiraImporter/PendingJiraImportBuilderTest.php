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

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectManager;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use UserManager;

#[DisableReturnValueGenerationForTestDoubles]
final class PendingJiraImportBuilderTest extends TestCase
{
    private UserManager&MockObject $user_manager;
    private ProjectManager&MockObject $project_manager;
    private PendingJiraImportBuilder $builder;

    protected function setUp(): void
    {
        $this->user_manager    = $this->createMock(UserManager::class);
        $this->project_manager = $this->createMock(ProjectManager::class);

        $this->builder = new PendingJiraImportBuilder($this->project_manager, $this->user_manager);
    }

    public function testItRaisesExceptionIfProjectIsNotValid(): void
    {
        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withError()->build());
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::anActiveUser()->build());

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItRaisesExceptionIfProjectIsNotActive(): void
    {
        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withStatusSuspended()->build());
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::anActiveUser()->build());

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItRaisesExceptionIfUserIsNotAlive(): void
    {
        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::aUser()->withStatus(PFUser::STATUS_SUSPENDED)->build());

        $this->expectException(UnableToBuildPendingJiraImportException::class);
        $this->builder->buildFromRow($this->aPendingImportRow());
    }

    public function testItReturnsAPendingJiraImport(): void
    {
        $this->project_manager->method('getProject')->willReturn(ProjectTestBuilder::aProject()->build());
        $this->user_manager->method('getUserById')->willReturn(UserTestBuilder::anActiveUser()->build());

        $pending_import = $this->builder->buildFromRow($this->aPendingImportRow());
        self::assertEquals(12, $pending_import->getId());
    }

    private function aPendingImportRow(): array
    {
        return [
            'id'                   => 12,
            'created_on'           => 0,
            'jira_server'          => '',
            'jira_issue_type_name' => '',
            'jira_issue_type_id'   => '',
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
