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
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class SubmittedOnMetadataTest extends CrossTrackerFieldTestCase
{
    private PFUser $project_member;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private int $release_artifact_past_1_id;
    private int $release_artifact_today_id;
    private int $sprint_artifact_past_2_id;
    private int $sprint_artifact_today_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project              = $core_builder->buildProject();
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');

        $release_subon_field_id = $tracker_builder->buildSubmittedOnField($this->release_tracker->getId());
        $sprint_subon_field_id  = $tracker_builder->buildSubmittedOnField($this->sprint_tracker->getId());

        $tracker_builder->setReadPermission(
            $release_subon_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_subon_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $today_timestamp  = (new DateTime('now'))->getTimestamp();
        $past_1_timestamp = (new DateTime('2023-03-08 10:25'))->getTimestamp();
        $past_2_timestamp = (new DateTime('2023-03-08 15:52'))->getTimestamp();

        $this->release_artifact_past_1_id = $tracker_builder->buildArtifact($this->release_tracker->getId(), $past_1_timestamp);
        $this->release_artifact_today_id  = $tracker_builder->buildArtifact($this->release_tracker->getId(), $today_timestamp);
        $this->sprint_artifact_past_2_id  = $tracker_builder->buildArtifact($this->sprint_tracker->getId(), $past_2_timestamp);
        $this->sprint_artifact_today_id   = $tracker_builder->buildArtifact($this->sprint_tracker->getId(), $today_timestamp);

        $tracker_builder->buildLastChangeset($this->release_artifact_past_1_id);
        $tracker_builder->buildLastChangeset($this->release_artifact_today_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_past_2_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_today_id);
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerReport $report, PFUser $user): array
    {
        $artifacts = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 10, 0)
            ->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on = '2023-03-08'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on = '2023-03-08 10:25'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on = '2023-03-08' OR @submitted_on = '1970-01-01'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testNotEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on != '2023-03-08'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testNotEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on != '2023-03-08 10:25'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on != '2023-03-08' AND @submitted_on != '1970-01-01'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testLesserThanDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on < '2023-03-09'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on < '2023-03-08 15:52'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testLesserThanToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on < NOW()",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on < NOW() OR @submitted_on < '1970-01-01'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on <= '2023-03-08'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on <= '2023-03-08 15:52'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testLesserThanOrEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on <= NOW()",
                [$this->release_tracker, $this->sprint_tracker],
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
            new CrossTrackerReport(
                1,
                "@submitted_on <= NOW() OR @submitted_on <= '1970-01-01'",
                [$this->release_tracker, $this->sprint_tracker],
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
            new CrossTrackerReport(
                1,
                "@submitted_on > '2023-03-08'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testGreaterThanDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on > '2023-03-08 10:25'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(3, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testGreaterThanYesterday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on > NOW() - 1d",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on > '2023-03-08' OR @submitted_on > NOW()",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testGreaterThanOrEqualDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on >= '2023-03-08'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing([
            $this->release_artifact_today_id, $this->release_artifact_past_1_id,
            $this->sprint_artifact_today_id, $this->sprint_artifact_past_2_id,
        ], $artifacts);
    }

    public function testGreaterThanOrEqualDatetime(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on >= '2023-03-08 10:25'",
                [$this->release_tracker, $this->sprint_tracker],
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
            new CrossTrackerReport(
                1,
                "@submitted_on >= NOW()",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on >= '2023-03-08' OR @submitted_on >= NOW()",
                [$this->release_tracker, $this->sprint_tracker],
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
            new CrossTrackerReport(
                1,
                "@submitted_on BETWEEN('2023-03-08 02:47', '2023-03-08 12:16')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id], $artifacts);
    }

    public function testBetweenDate(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on BETWEEN('2023-03-01', '2023-03-31')",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_past_1_id, $this->sprint_artifact_past_2_id], $artifacts);
    }

    public function testBetweenYesterdayAndTomorrow(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on BETWEEN(NOW() - 1d, NOW() + 1d)",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_today_id, $this->sprint_artifact_today_id], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "@submitted_on BETWEEN(NOW() - 1d, NOW() + 1d) OR @submitted_on BETWEEN('2023-03-01', '2023-03-31')",
                [$this->release_tracker, $this->sprint_tracker],
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
