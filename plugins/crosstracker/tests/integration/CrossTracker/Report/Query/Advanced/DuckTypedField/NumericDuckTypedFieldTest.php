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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use Tracker;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\SearchOnDuckTypedFieldsConfig;
use Tuleap\CrossTracker\Tests\Report\ArtifactReportFactoryInstantiator;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesAreInvalidException;
use Tuleap\Tracker\Report\Query\Advanced\SearchablesDoNotExistException;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class NumericDuckTypedFieldTest extends TestIntegrationTestCase
{
    private TrackerDatabaseBuilder $database_builder;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private Tracker $task_tracker;
    private \PFUser $project_member;
    private \PFUser $outsider_user;
    private Tracker $epic_tracker;
    private int $release_empty_id;
    private int $sprint_empty_id;
    private int $release_with_5_id;
    private int $sprint_with_5_id;
    private int $sprint_with_3_id;
    private int $release_with_3_id;
    private CoreDatabaseBuilder $core_builder;

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        \ForgeConfig::setFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $this->database_builder = new TrackerDatabaseBuilder($db);
        $this->core_builder     = new CoreDatabaseBuilder($db);

        $project    = $this->core_builder->buildProject();
        $project_id = (int) $project->getID();

        $this->release_tracker = $this->database_builder->buildTracker($project_id, "Release");
        $this->sprint_tracker  = $this->database_builder->buildTracker($project_id, "Sprint");
        $this->task_tracker    = $this->database_builder->buildTracker($project_id, "Task");
        $this->epic_tracker    = $this->database_builder->buildTracker($project_id, "Epic");

        $release_initial_effort_field_id  = $this->database_builder->buildIntField(
            $this->release_tracker->getId(),
            'initial_effort'
        );
        $sprint_initial_effort_field_id   = $this->database_builder->buildFloatField(
            $this->sprint_tracker->getId(),
            'initial_effort'
        );
        $initial_effort_computed_field_id = $this->database_builder->buildComputedField(
            $this->task_tracker->getId(),
            'initial_effort'
        );
        $initial_effort_read_field_id     = $this->database_builder->buildFloatField(
            $this->epic_tracker->getId(),
            'initial_effort'
        );

        $this->outsider_user  = $this->core_builder->buildUser('outsider', 'User OutsideProject', 'outsider@example.com');
        $this->project_member = $this->core_builder->buildUser('janwar', 'Jorge Anwar', 'janwar@example.com');
        $this->core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $this->database_builder->setReadPermission(
            $release_initial_effort_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $sprint_initial_effort_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $initial_effort_computed_field_id,
            \ProjectUGroup::PROJECT_MEMBERS
        );
        $this->database_builder->setReadPermission(
            $initial_effort_read_field_id,
            \ProjectUGroup::PROJECT_ADMIN
        );

        $this->release_empty_id  = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $this->release_with_5_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $this->release_with_3_id = $this->database_builder->buildArtifact($this->release_tracker->getId());
        $this->sprint_empty_id   = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_with_5_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_with_3_id  = $this->database_builder->buildArtifact($this->sprint_tracker->getId());

        $this->database_builder->buildLastChangeset($this->release_empty_id);
        $release_with_5_changeset_id = $this->database_builder->buildLastChangeset($this->release_with_5_id);
        $release_with_3_changeset_id = $this->database_builder->buildLastChangeset($this->release_with_3_id);
        $this->database_builder->buildLastChangeset($this->sprint_empty_id);
        $sprint_with_5_changeset_id = $this->database_builder->buildLastChangeset($this->sprint_with_5_id);
        $sprint_with_3_changeset_id = $this->database_builder->buildLastChangeset($this->sprint_with_3_id);

        $this->database_builder->buildIntValue($release_with_5_changeset_id, $release_initial_effort_field_id, 5);
        $this->database_builder->buildIntValue($release_with_3_changeset_id, $release_initial_effort_field_id, 3);
        $this->database_builder->buildFloatValue($sprint_with_5_changeset_id, $sprint_initial_effort_field_id, 5);
        $this->database_builder->buildFloatValue($sprint_with_3_changeset_id, $sprint_initial_effort_field_id, 3);
    }

    /**
     * @return list<int>
     * @throws SearchablesDoNotExistException
     * @throws SearchablesAreInvalidException
     */
    private function getMatchingArtifactIds(CrossTrackerReport $report, \PFUser $user): array
    {
        $artifacts = (new ArtifactReportFactoryInstantiator())
            ->getFactory()
            ->getArtifactsMatchingReport($report, $user, 5, 0)
            ->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_empty_id, $this->sprint_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort=5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort = '' OR initial_effort = 5",
                [$this->release_tracker, $this->sprint_tracker]
            ),
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
            new CrossTrackerReport(
                1,
                "initial_effort != ''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->release_with_3_id, $this->sprint_with_5_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort != 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_empty_id, $this->sprint_empty_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testMultipleNotEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort != 5 AND initial_effort != ''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort < 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testMultipleLesserThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort < 5 OR initial_effort < 8',
                [$this->release_tracker, $this->sprint_tracker]
            ),
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
            new CrossTrackerReport(
                1,
                'initial_effort <= 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testMultipleLesserThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort <= 5 OR initial_effort <= 8',
                [$this->release_tracker, $this->sprint_tracker]
            ),
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
            new CrossTrackerReport(
                1,
                'initial_effort > 3',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }

    public function testMultipleGreaterThan(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort > 3 OR initial_effort > 1',
                [$this->release_tracker, $this->sprint_tracker]
            ),
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
            new CrossTrackerReport(
                1,
                'initial_effort >= 3',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_5_id, $this->sprint_with_5_id, $this->release_with_3_id, $this->sprint_with_3_id],
            $artifacts
        );
    }

    public function testMultipleGreaterThanOrEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort >= 3 OR initial_effort >= 5',
                [$this->release_tracker, $this->sprint_tracker]
            ),
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
            new CrossTrackerReport(
                1,
                'initial_effort BETWEEN(2, 4)',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_3_id, $this->sprint_with_3_id], $artifacts);
    }

    public function testMultipleBetween(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort BETWEEN(2, 4) OR initial_effort BETWEEN(5, 8)',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(4, $artifacts);
        self::assertEqualsCanonicalizing(
            [$this->release_with_3_id, $this->sprint_with_3_id, $this->release_with_5_id, $this->sprint_with_5_id],
            $artifacts
        );
    }

    public function testInvalidFieldComparison(): void
    {
        $this->expectException(SearchablesAreInvalidException::class);
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=''",
                [$this->release_tracker, $this->task_tracker]
            ),
            $this->project_member
        );
    }

    public function testUserCanNotReadAnyField(): void
    {
        $this->expectException(SearchablesDoNotExistException::class);
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=''",
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->outsider_user
        );
    }

    public function testUserCanNotReadEpicField(): void
    {
        $epic_empty_id = $this->database_builder->buildArtifact($this->epic_tracker->getId());
        $this->database_builder->buildLastChangeset($epic_empty_id);

        $artifact_user_can_read = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=''",
                [$this->epic_tracker, $this->release_tracker]
            ),
            $this->project_member
        );

        self::assertCount(1, $artifact_user_can_read);
        self::assertEqualsCanonicalizing([$this->release_empty_id], $artifact_user_can_read);
    }

    public function testIntegerFieldComparisonIsValid(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'initial_effort > 3.00',
                [$this->release_tracker, $this->sprint_tracker]
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertNotContains($this->release_empty_id, $artifacts);
        self::assertNotContains($this->sprint_empty_id, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_with_5_id, $this->sprint_with_5_id], $artifacts);
    }

    public function testIntegerFieldComparisonToEmptyIsNotValid(): void
    {
        $epic_empty_id = $this->database_builder->buildArtifact($this->epic_tracker->getId());
        $this->database_builder->buildLastChangeset($epic_empty_id);

        $this->expectException(SearchablesAreInvalidException::class);
        $this->expectExceptionMessage("cannot be compared to the empty string with > operator");
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort > ''",
                [$this->epic_tracker, $this->release_tracker]
            ),
            $this->project_member
        );
    }

    public function testIntegerFieldComparisonToMyselfIsNotValid(): void
    {
        $epic_empty_id = $this->database_builder->buildArtifact($this->epic_tracker->getId());
        $this->database_builder->buildLastChangeset($epic_empty_id);

        $this->expectException(SearchablesAreInvalidException::class);
        $this->expectExceptionMessage("cannot be compared to MYSELF()");
        $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "initial_effort=MYSELF()",
                [$this->epic_tracker, $this->release_tracker]
            ),
            $this->project_member
        );
    }
}
