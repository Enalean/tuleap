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

namespace Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField;

use DateTime;
use ForgeConfig;
use PFUser;
use ProjectUGroup;
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

final class DateDuckTypedFieldTest extends TestIntegrationTestCase
{
    private PFUser $project_member;
    private Tracker $release_tracker;
    private Tracker $sprint_tracker;
    private int $release_artifact_empty_id;
    private int $release_artifact_with_date_id;
    private int $release_artifact_with_now_id;
    private int $sprint_artifact_empty_id;
    private int $sprint_artifact_with_date_id;

    protected function setUp(): void
    {
        $db = DBFactory::getMainTuleapDBConnection()->getDB();
        ForgeConfig::setFeatureFlag(SearchOnDuckTypedFieldsConfig::FEATURE_FLAG_SEARCH_DUCK_TYPED_FIELDS, '1');
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project              = $core_builder->buildProject();
        $project_id           = (int) $project->getID();
        $this->project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->project_member->getId(), $project_id);

        $this->release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $this->sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');

        $release_date_field_id = $tracker_builder->buildDateField(
            $this->release_tracker->getId(),
            'date_field',
            false
        );
        $sprint_date_field_id  = $tracker_builder->buildDateField(
            $this->sprint_tracker->getId(),
            'date_field',
            false
        );

        $tracker_builder->setReadPermission(
            $release_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->release_artifact_empty_id     = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_date_id = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->release_artifact_with_now_id  = $tracker_builder->buildArtifact($this->release_tracker->getId());
        $this->sprint_artifact_empty_id      = $tracker_builder->buildArtifact($this->sprint_tracker->getId());
        $this->sprint_artifact_with_date_id  = $tracker_builder->buildArtifact($this->sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($this->release_artifact_empty_id);
        $release_artifact_with_date_changeset = $tracker_builder->buildLastChangeset($this->release_artifact_with_date_id);
        $release_artifact_with_now_changeset  = $tracker_builder->buildLastChangeset($this->release_artifact_with_now_id);
        $tracker_builder->buildLastChangeset($this->sprint_artifact_empty_id);
        $sprint_artifact_with_date_changeset = $tracker_builder->buildLastChangeset($this->sprint_artifact_with_date_id);

        $tracker_builder->buildDateValue(
            $release_artifact_with_date_changeset,
            $release_date_field_id,
            (new DateTime('2024-02-12'))->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $release_artifact_with_now_changeset,
            $release_date_field_id,
            (new DateTime())->setTime(0, 0)->getTimestamp()
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_date_changeset,
            $sprint_date_field_id,
            (new DateTime('2024-03-12'))->getTimestamp()
        );
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
            ->getArtifactsMatchingReport($report, $user, 5, 0)
            ->getArtifacts();
        return array_values(array_map(static fn(Artifact $artifact) => $artifact->getId(), $artifacts));
    }

    public function testEqualEmpty(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "date_field = ''",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_empty_id, $this->sprint_artifact_empty_id], $artifacts);
    }

    public function testEqualValue(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "date_field = '2024-02-12'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id], $artifacts);
    }

    public function testEqualToday(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                'date_field = NOW()',
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(1, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_now_id], $artifacts);
    }

    public function testMultipleEqual(): void
    {
        $artifacts = $this->getMatchingArtifactIds(
            new CrossTrackerReport(
                1,
                "date_field = '2024-02-12' OR date_field = '2024-03-12'",
                [$this->release_tracker, $this->sprint_tracker],
            ),
            $this->project_member
        );

        self::assertCount(2, $artifacts);
        self::assertEqualsCanonicalizing([$this->release_artifact_with_date_id, $this->sprint_artifact_with_date_id], $artifacts);
    }
}
