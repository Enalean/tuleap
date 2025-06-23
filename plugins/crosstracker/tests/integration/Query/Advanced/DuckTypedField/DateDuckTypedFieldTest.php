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

namespace Tuleap\CrossTracker\Query\Advanced\DuckTypedField;

use DateTime;
use PFUser;
use ProjectUGroup;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DateDuckTypedFieldTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_date_id;
    private int $release_artifact_with_now_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_date_id;
    private int $sprint_artifact_with_future_id;
    private int $task_artifact_with_date_id;

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

        $release_date_field_id = $tracker_builder->buildDateField(
            $release_tracker->getId(),
            'date_field',
            false
        );
        $sprint_date_field_id  = $tracker_builder->buildDateField(
            $sprint_tracker->getId(),
            'date_field',
            false
        );
        $task_date_field_id    = $tracker_builder->buildDateField(
            $task_tracker->getId(),
            'date_field',
            false
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $task_date_field_id,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_artifact_empty_id      = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_date_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_with_now_id   = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_empty_id       = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_date_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_with_future_id = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_artifact_with_date_id     = $tracker_builder->buildArtifact($task_tracker->getId());

        $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_date_changeset = $tracker_builder->buildLastChangeset($this->release_artifact_with_date_id);
        $release_artifact_with_now_changeset  = $tracker_builder->buildLastChangeset($this->release_artifact_with_now_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_date_changeset   = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_date_id);
        $sprint_artifact_with_future_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_future_id);
        $task_artifact_with_date_changeset     = $tracker_builder->buildLastChangeset($this->task_artifact_with_date_id);

        $tracker_builder->buildDateValue(
            $release_artifact_with_date_changeset,
            $release_date_field_id,
            (new DateTime('2023-02-12'))->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $release_artifact_with_now_changeset,
            $release_date_field_id,
            (new DateTime())->setTime(0, 0)->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_date_changeset,
            $sprint_date_field_id,
            (new DateTime('2023-03-12'))->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_future_changeset,
            $sprint_date_field_id,
            (new DateTime('tomorrow'))->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $task_artifact_with_date_changeset,
            $task_date_field_id,
            (new DateTime('2023-02-12'))->getTimestamp()
        );
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field = ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field = '2023-02-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field = '2023-02-12'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->task_artifact_with_date_id], $artifacts);
    }

    public function testEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field = NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field = '2023-02-12' OR date_field = '2023-03-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testNotEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field != ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id,
        ], $artifacts);
    }

    public function testNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field != '2023-02-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_date_id,
            $this->sprint_artifact_with_future_id,
        ], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field != '2023-03-12'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(6, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_future_id,
            $this->task_artifact_with_date_id,
        ], $artifacts);
    }

    public function testNotEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field != NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_empty_id, $this->release_artifact_with_date_id,
            $this->sprint_artifact_empty_id, $this->sprint_artifact_with_date_id,
            $this->sprint_artifact_with_future_id,
        ], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field != '2023-02-12' AND date_field != ''",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id, $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id], $artifacts);
    }

    public function testLesserThanValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field < '2023-03-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id], $artifacts);
    }

    public function testPermissionsLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field < '2023-03-12'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->task_artifact_with_date_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field < '2023-03-12' AND date_field < NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id], $artifacts);
    }

    public function testLesserThanToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field < NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testLesserThanOrEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field <= '2023-03-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testPermissionsLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field <= '2023-03-12'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id, $this->task_artifact_with_date_id,
        ], $artifacts);
    }

    public function testMultipleLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field <= '2023-03-12' AND date_field <= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testLesserThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field <= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->release_artifact_with_now_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testGreaterThanValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field > '2023-02-11'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id,
        ], $artifacts);
    }

    public function testPermissionsGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field > '2023-02-11'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id,
            $this->task_artifact_with_date_id,
        ], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field > '2023-02-12' AND date_field > '2023-03-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id, $this->sprint_artifact_with_future_id], $artifacts);
    }

    public function testGreaterThanToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field > NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->sprint_artifact_with_future_id], $artifacts);
    }

    public function testGreaterThanOrEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field >= '2023-02-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id,
        ], $artifacts);
    }

    public function testPermissionsGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field >= '2023-02-12'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(5, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->release_artifact_with_now_id,
            $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id,
            $this->task_artifact_with_date_id,
        ], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field >= '2023-02-12' AND date_field >= '2023-03-12'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id, $this->sprint_artifact_with_date_id, $this->sprint_artifact_with_future_id], $artifacts);
    }

    public function testGreaterThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field >= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id, $this->sprint_artifact_with_future_id], $artifacts);
    }

    public function testBetweenValues(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field BETWEEN('2023-02-01', '2023-03-31')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }

    public function testPermissionsBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field BETWEEN('2023-02-01', '2023-03-31')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id, $this->task_artifact_with_date_id,
        ], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field BETWEEN('2023-02-01', '2023-02-28') OR date_field BETWEEN(NOW() - 1w, NOW())",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->release_artifact_with_now_id], $artifacts);
    }

    public function testBetweenYesterdayAndTomorrow(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE date_field BETWEEN(NOW() - 1d, NOW() + 1d)",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id, $this->sprint_artifact_with_future_id], $artifacts);
    }
}
