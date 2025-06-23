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

namespace Tuleap\CrossTracker\Query\Advanced\Metadata;

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
final class LastUpdateDateMetadataTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $project_member;
    private PFUser $project_admin;
    private int $release_artifact_past_1_id;
    private int $release_artifact_today_id;
    private int $sprint_artifact_past_2_id;
    private int $sprint_artifact_today_id;
    private int $task_artifact_past_1_id;
    private int $task_artifact_today_id;

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

        $task_lud_field_id = $tracker_builder->buildLastUpdateDateField($task_tracker->getId());
        $tracker_builder->grantReadPermissionOnField($task_lud_field_id, ProjectUGroup::PROJECT_ADMIN);

        $this->release_artifact_past_1_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->release_artifact_today_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $this->sprint_artifact_past_2_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->sprint_artifact_today_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->task_artifact_past_1_id    = $tracker_builder->buildArtifact($task_tracker->getId());
        $this->task_artifact_today_id     = $tracker_builder->buildArtifact($task_tracker->getId());

        $today_timestamp  = (new DateTime('now'))->getTimestamp();
        $past_1_timestamp = (new DateTime('2023-03-08 10:25'))->getTimestamp();
        $past_2_timestamp = (new DateTime('2023-03-08 15:52'))->getTimestamp();

        $tracker_builder->buildLastChangeset($this->release_artifact_past_1_id, $past_1_timestamp);
        $tracker_builder->buildLastChangeset($this->release_artifact_today_id, $today_timestamp);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_past_2_id, $past_2_timestamp);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_today_id, $today_timestamp);
        $tracker_builder->buildLastChangeset($this->task_artifact_past_1_id, $past_1_timestamp);
        $tracker_builder->buildLastChangeset($this->task_artifact_today_id, $today_timestamp);
    }

    public function testEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date = '2023-03-08'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date = '2023-03-08'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date = '2023-03-08 10:25'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date = '2023-03-08' OR @last_update_date = '1970-01-01'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testNotEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date != '2023-03-08'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date != '2023-03-08'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->task_artifact_today_id], $artifacts);
    }

    public function testNotEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date != '2023-03-08 10:25'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date != '2023-03-08' AND @last_update_date != '1970-01-01'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testLesserThanDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date < '2023-03-09'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date < '2023-03-09'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date < '2023-03-08 15:52'",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date < NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date < NOW() OR @last_update_date < '1970-01-01'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date <= '2023-03-08'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date <= '2023-03-08'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanOrEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date <= '2023-03-08 15:52'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date <= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_past_1_id, $this->release_artifact_today_id,
            $this->sprint_artifact_past_2_id, $this->sprint_artifact_today_id,
        ], $artifacts);
    }

    public function testMultipleLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date <= NOW() OR @last_update_date <= '1970-01-01'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_past_1_id, $this->release_artifact_today_id,
            $this->sprint_artifact_past_2_id, $this->sprint_artifact_today_id,
        ], $artifacts);
    }

    public function testGreaterThanDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date > '2023-03-08'",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testPermissionsGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date > '2023-03-08'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->task_artifact_today_id], $artifacts);
    }

    public function testGreaterThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date > '2023-03-08 10:25'",
                )->build(),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testGreaterThanYesterday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date > NOW() - 1d",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date > '2023-03-08' OR @last_update_date > NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testGreaterThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date >= '2023-03-08'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }

    public function testPermissionsGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date >= '2023-03-08'",
                )->build(),
            $this->project_admin
        );

        self::assertCount(6, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
            $this->task_artifact_today_id, $this->task_artifact_past_1_id,
        ], $artifacts);
    }

    public function testGreaterThanOrEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date >= '2023-03-08 10:25'",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }

    public function testGreaterThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date >= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date >= '2023-03-08' OR @last_update_date >= NOW()",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }

    public function testBetweenDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date BETWEEN('2023-03-08 02:47', '2023-03-08 12:16')",
                )->build(),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testPermissionsBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date BETWEEN('2023-03-08 02:47', '2023-03-08 12:16')",
                )->build(),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testBetweenDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date BETWEEN('2023-03-01', '2023-03-31')",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testBetweenYesterdayAndTomorrow(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date BETWEEN(NOW() - 1d, NOW() + 1d)",
                )->build(),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    "SELECT @id FROM @project = 'self' WHERE @last_update_date BETWEEN(NOW() - 1d, NOW() + 1d) OR @last_update_date BETWEEN('2023-03-01', '2023-03-31')",
                )->build(),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }
}
