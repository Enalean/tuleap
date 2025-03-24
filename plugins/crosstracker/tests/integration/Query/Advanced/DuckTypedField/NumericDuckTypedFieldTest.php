<?php
/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class NumericDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $release_empty_id;
    private int $sprint_empty_id;
    private int $release_with_5_id;
    private int $sprint_with_5_id;
    private int $sprint_with_3_id;
    private int $release_with_3_id;
    private int $task_with_5_id;
    private int $task_with_3_id;

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
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($task_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_initial_effort_field_id = $tracker_builder->buildIntField(
            $release_tracker->getId(),
            'initial_effort'
        );
        $sprint_initial_effort_field_id  = $tracker_builder->buildFloatField(
            $sprint_tracker->getId(),
            'initial_effort'
        );
        $task_initial_effort_field_id    = $tracker_builder->buildIntField(
            $task_tracker->getId(),
            'initial_effort'
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_initial_effort_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_initial_effort_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $task_initial_effort_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_empty_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_with_5_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_with_3_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_empty_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_with_5_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_with_3_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_with_5_id    = $tracker_builder->buildArtifact($task_tracker->getId());
        $this->task_with_3_id    = $tracker_builder->buildArtifact($task_tracker->getId());

        $tracker_builder->buildLastChangeset($this->release_empty_id);
        $release_with_5_changeset = $tracker_builder->buildLastChangeset($this->release_with_5_id);
        $release_with_3_changeset = $tracker_builder->buildLastChangeset($this->release_with_3_id);
        $tracker_builder->buildLastChangeset($this->sprint_empty_id);
        $sprint_with_5_changeset = $tracker_builder->buildLastChangeset($this->sprint_with_5_id);
        $sprint_with_3_changeset = $tracker_builder->buildLastChangeset($this->sprint_with_3_id);
        $task_with_5_changeset   = $tracker_builder->buildLastChangeset($this->task_with_5_id);
        $task_with_3_changeset   = $tracker_builder->buildLastChangeset($this->task_with_3_id);

        $tracker_builder->buildIntValue($release_with_5_changeset, $release_initial_effort_field_id, 5);
        $tracker_builder->buildIntValue($release_with_3_changeset, $release_initial_effort_field_id, 3);
        $tracker_builder->buildFloatValue($sprint_with_5_changeset, $sprint_initial_effort_field_id, 5);
        $tracker_builder->buildFloatValue($sprint_with_3_changeset, $sprint_initial_effort_field_id, 3);
        $tracker_builder->buildIntValue($task_with_5_changeset, $task_initial_effort_field_id, 5);
        $tracker_builder->buildIntValue($task_with_3_changeset, $task_initial_effort_field_id, 3);
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        "SELECT @id FROM @project = 'self' WHERE initial_effort=''",
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_empty_id, $this->sprint_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort = 5',
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort = 5',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->task_with_5_id],
            $artifacts
        );
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        "SELECT @id FROM @project = 'self' WHERE initial_effort = '' OR initial_effort = 5",
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_empty_id, $this->sprint_empty_id, $this->release_with_5_id, $this->sprint_with_5_id],
            $artifacts
        );
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        "SELECT @id FROM @project = 'self' WHERE initial_effort != ''",
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->release_with_3_id, $this->sprint_with_5_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testNotEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort != 5',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_empty_id, $this->release_with_3_id,
            $this->sprint_empty_id, $this->sprint_with_3_id,
        ], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort != 5',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_empty_id, $this->release_with_3_id,
            $this->sprint_empty_id, $this->sprint_with_3_id,
            $this->task_with_3_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        "SELECT @id FROM @project = 'self' WHERE initial_effort != 5 AND initial_effort != ''",
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort < 5',
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testPermissionsLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort < 5',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id, $this->task_with_3_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort < 5 OR initial_effort < 8',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort <= 5',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_with_5_id, $this->release_with_3_id,
            $this->sprint_with_5_id, $this->sprint_with_3_id,
        ], $artifacts);
    }

    public function testPermissionsLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort <= 5',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(6, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_with_5_id, $this->release_with_3_id,
            $this->sprint_with_5_id, $this->sprint_with_3_id,
            $this->task_with_5_id, $this->task_with_3_id,
        ], $artifacts);
    }

    public function testMultipleLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort <= 5 OR initial_effort <= 8',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort > 3',
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }

    public function testPermissionsGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort > 3',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id, $this->task_with_5_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort > 3 OR initial_effort > 1',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->release_with_3_id, $this->sprint_with_5_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort >= 3',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_with_5_id, $this->release_with_3_id,
            $this->sprint_with_5_id, $this->sprint_with_3_id,
        ], $artifacts);
    }

    public function testPermissionsGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort >= 3',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(6, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_with_5_id, $this->release_with_3_id,
            $this->sprint_with_5_id, $this->sprint_with_3_id,
            $this->task_with_5_id, $this->task_with_3_id,
        ], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort >= 3 OR initial_effort >= 5',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort BETWEEN(2, 4)',
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testPermissionsBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort BETWEEN(2, 4)',
                    )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id, $this->task_with_3_id], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort BETWEEN(2, 4) OR initial_effort BETWEEN(5, 8)',
                    )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_3_id, $this->sprint_with_3_id, $this->release_with_5_id, $this->sprint_with_5_id],
            $artifacts
        );
    }

    public function testIntegerFieldComparisonIsValid(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                    ->withUUID($this->uuid)->withTqlQuery(
                        'SELECT @id FROM @project = "self" WHERE initial_effort > 3.00',
                    )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }
}
