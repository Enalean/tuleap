<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\Query\Advanced\Metadata;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class StatusMetadataTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $release_artifact_open_id;
    private int $release_artifact_close_id;
    private int $sprint_artifact_open_id;
    private int $sprint_artifact_close_id;
    private int $task_artifact_open_id;
    private int $task_artifact_close_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project              = $core_builder->buildProject('project_name');
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $this->project_admin  = $core_builder->buildUser('project_admin', 'Project Admin', 'project_admin@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $this->project_admin->getId(), $project_id);
        $core_builder->addUserToProjectAdmins((int) $this->project_admin->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($task_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_status_field_id = $tracker_builder->buildStaticListField($release_tracker->getId(), 'release_status', 'sb');
        $release_status_values   = $tracker_builder->buildOpenAndClosedValuesForField($release_status_field_id, $release_tracker->getId(), ['Open'], ['Closed']);
        $sprint_status_field_id  = $tracker_builder->buildStaticListField($sprint_tracker->getId(), 'sprint_status', 'sb');
        $sprint_status_values    = $tracker_builder->buildOpenAndClosedValuesForField($sprint_status_field_id, $sprint_tracker->getId(), ['Open'], ['Closed']);
        $task_status_field_id    = $tracker_builder->buildStaticListField($task_tracker->getId(), 'task_status', 'sb');
        $task_status_values      = $tracker_builder->buildOpenAndClosedValuesForField($task_status_field_id, $task_tracker->getId(), ['Open'], ['Closed']);

        $tracker_builder->grantReadPermissionOnField(
            $release_status_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_status_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $task_status_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_artifact_open_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_close_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_open_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_close_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_artifact_open_id     = $tracker_builder->buildArtifact($task_tracker->getId());
        $this->task_artifact_close_id    = $tracker_builder->buildArtifact($task_tracker->getId());

        $release_artifact_open_changeset  = $tracker_builder->buildLastChangeset($this->release_artifact_open_id);
        $release_artifact_close_changeset = $tracker_builder->buildLastChangeset($this->release_artifact_close_id);
        $sprint_artifact_open_changeset   = $tracker_builder->buildLastChangeset($this->sprint_artifact_open_id);
        $sprint_artifact_close_changeset  = $tracker_builder->buildLastChangeset($this->sprint_artifact_close_id);
        $task_artifact_open_changeset     = $tracker_builder->buildLastChangeset($this->task_artifact_open_id);
        $task_artifact_close_changeset    = $tracker_builder->buildLastChangeset($this->task_artifact_close_id);

        $tracker_builder->buildListValue($release_artifact_open_changeset, $release_status_field_id, $release_status_values['open'][0]);
        $tracker_builder->buildListValue($release_artifact_close_changeset, $release_status_field_id, $release_status_values['closed'][0]);
        $tracker_builder->buildListValue($sprint_artifact_open_changeset, $sprint_status_field_id, $sprint_status_values['open'][0]);
        $tracker_builder->buildListValue($sprint_artifact_close_changeset, $sprint_status_field_id, $sprint_status_values['closed'][0]);
        $tracker_builder->buildListValue($task_artifact_open_changeset, $task_status_field_id, $task_status_values['open'][0]);
        $tracker_builder->buildListValue($task_artifact_close_changeset, $task_status_field_id, $task_status_values['closed'][0]);
    }

    public function testEqualOpen(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status = OPEN()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_open_id, $this->sprint_artifact_open_id], $artifacts);
    }

    public function testPermissionsEqualOpen(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status = OPEN()",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_open_id, $this->sprint_artifact_open_id, $this->task_artifact_open_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status = OPEN() OR @status = OPEN()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_open_id, $this->sprint_artifact_open_id], $artifacts);
    }

    public function testNotEqualOpen(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status != OPEN()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_close_id, $this->sprint_artifact_close_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status != OPEN()",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_close_id, $this->sprint_artifact_close_id, $this->task_artifact_close_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @status != OPEN() AND @status != OPEN()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_close_id, $this->sprint_artifact_close_id], $artifacts);
    }
}
