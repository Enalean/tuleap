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

use DateTime;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class SubmittedOnMetadataTest extends CrossTrackerFieldTestCase
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
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $task_tracker    = $tracker_builder->buildTracker($project_id, 'Task');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($task_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $task_subon_field_id = $tracker_builder->buildSubmittedOnField($task_tracker->getId());
        $tracker_builder->grantReadPermissionOnField($task_subon_field_id, ProjectUGroup::PROJECT_ADMIN);

        $today_timestamp  = (new DateTime('now'))->getTimestamp();
        $past_1_timestamp = (new DateTime('2023-03-08 10:25'))->getTimestamp();
        $past_2_timestamp = (new DateTime('2023-03-08 15:52'))->getTimestamp();

        $this->release_artifact_past_1_id = $tracker_builder->buildArtifact($release_tracker->getId(), $past_1_timestamp);
        $this->release_artifact_today_id  = $tracker_builder->buildArtifact($release_tracker->getId(), $today_timestamp);
        $this->sprint_artifact_past_2_id  = $tracker_builder->buildArtifact($sprint_tracker->getId(), $past_2_timestamp);
        $this->sprint_artifact_today_id   = $tracker_builder->buildArtifact($sprint_tracker->getId(), $today_timestamp);
        $this->task_artifact_past_1_id    = $tracker_builder->buildArtifact($task_tracker->getId(), $past_1_timestamp);
        $this->task_artifact_today_id     = $tracker_builder->buildArtifact($task_tracker->getId(), $today_timestamp);

        $tracker_builder->buildLastChangeset($this->release_artifact_past_1_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_today_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_past_2_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_today_id);
        $tracker_builder->buildLastChangeset($this->task_artifact_past_1_id);
        $tracker_builder->buildLastChangeset($this->task_artifact_today_id);
    }

    public function testEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on = '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on = '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on = '2023-03-08 10:25'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on = '2023-03-08' OR @submitted_on = '1970-01-01'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testNotEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on != '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testPermissionsNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on != '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->task_artifact_today_id], $artifacts);
    }

    public function testNotEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on != '2023-03-08 10:25'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on != '2023-03-08' AND @submitted_on != '1970-01-01'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testLesserThanDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on < '2023-03-09'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on < '2023-03-09'",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on < '2023-03-08 15:52'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on < NOW()",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on < NOW() OR @submitted_on < '1970-01-01'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on <= '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testPermissionsLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on <= '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanOrEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on <= '2023-03-08 15:52'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on <= NOW()",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on <= NOW() OR @submitted_on <= '1970-01-01'",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on > '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testPermissionsGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on > '2023-03-08'",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->task_artifact_today_id], $artifacts);
    }

    public function testGreaterThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on > '2023-03-08 10:25'",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testGreaterThanYesterday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on > NOW() - 1d",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on > '2023-03-08' OR @submitted_on > NOW()",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testGreaterThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on >= '2023-03-08'",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on >= '2023-03-08'",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on >= '2023-03-08 10:25'",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on >= NOW()",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on >= '2023-03-08' OR @submitted_on >= NOW()",
                '',
                '',
                1,
            ),
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
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on BETWEEN('2023-03-08 02:47', '2023-03-08 12:16')",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testPermissionsBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on BETWEEN('2023-03-08 02:47', '2023-03-08 12:16')",
                '',
                '',
                1,
            ),
            $this->project_admin
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->task_artifact_past_1_id], $artifacts);
    }

    public function testBetweenDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on BETWEEN('2023-03-01', '2023-03-31')",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testBetweenYesterdayAndTomorrow(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on BETWEEN(NOW() - 1d, NOW() + 1d)",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @id FROM @project = 'self' WHERE @submitted_on BETWEEN(NOW() - 1d, NOW() + 1d) OR @submitted_on BETWEEN('2023-03-01', '2023-03-31')",
                '',
                '',
                1,
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }
}
