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

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use DateTime;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\DuckTypedField\FieldTypeRetrieverWrapper;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\CrossTrackerExpertQueryReportDao;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Date\DateSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Numeric\NumericSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\StaticList\StaticListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\Text\TextSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class DateSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private SelectBuilderVisitor $builder;
    private CrossTrackerExpertQueryReportDao $dao;
    /**
     * @var Tracker[]
     */
    private array $trackers;
    /**
     * @var list<int>
     */
    private array $artifact_ids;
    /**
     * @var array<int, ?int>
     */
    private array $expected_values;
    private PFUser $user;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject();
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

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

        $tracker_builder->setReadPermission(
            $release_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_date_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id      = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_date_id  = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_now_id   = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_empty_id       = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $sprint_artifact_with_date_id   = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $sprint_artifact_with_future_id = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->artifact_ids             = [
            $release_artifact_empty_id,
            $release_artifact_with_date_id,
            $release_artifact_with_now_id,
            $sprint_artifact_empty_id,
            $sprint_artifact_with_date_id,
            $sprint_artifact_with_future_id,
        ];

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_date_changeset = $tracker_builder->buildLastChangeset($release_artifact_with_date_id);
        $release_artifact_with_now_changeset  = $tracker_builder->buildLastChangeset($release_artifact_with_now_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_empty_id);
        $sprint_artifact_with_date_changeset   = $tracker_builder->buildLastChangeset($sprint_artifact_with_date_id);
        $sprint_artifact_with_future_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_with_future_id);

        $this->expected_values = [
            $release_artifact_empty_id      => null,
            $release_artifact_with_date_id  => (new DateTime('2023-02-12'))->getTimestamp(),
            $release_artifact_with_now_id   => (new DateTime())->setTime(0, 0)->getTimestamp(),
            $sprint_artifact_empty_id       => null,
            $sprint_artifact_with_date_id   => (new DateTime('2023-03-12'))->getTimestamp(),
            $sprint_artifact_with_future_id => (new DateTime('tomorrow'))->getTimestamp(),
        ];
        $tracker_builder->buildDateValue(
            $release_artifact_with_date_changeset,
            $release_date_field_id,
            (int) $this->expected_values[$release_artifact_with_date_id],
        );
        $tracker_builder->buildDateValue(
            $release_artifact_with_now_changeset,
            $release_date_field_id,
            (int) $this->expected_values[$release_artifact_with_now_id],
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_date_changeset,
            $sprint_date_field_id,
            (int) $this->expected_values[$sprint_artifact_with_date_id],
        );
        $tracker_builder->buildDateValue(
            $sprint_artifact_with_future_changeset,
            $sprint_date_field_id,
            (int) $this->expected_values[$sprint_artifact_with_future_id],
        );

        $this->builder = new SelectBuilderVisitor(new FieldSelectFromBuilder(
            Tracker_FormElementFactory::instance(),
            new FieldTypeRetrieverWrapper(Tracker_FormElementFactory::instance()),
            TrackersPermissionsRetriever::build(),
            new DateSelectFromBuilder(),
            new TextSelectFromBuilder(),
            new NumericSelectFromBuilder(),
            new StaticListSelectFromBuilder()
        ));
        $this->dao     = new CrossTrackerExpertQueryReportDao();
    }

    private function getQueryResults(): array
    {
        $select_from = $this->builder->buildSelectFrom(
            [new Field('date_field')],
            $this->trackers,
            $this->user,
        );

        return $this->dao->searchArtifactsColumnsMatchingIds($select_from, $this->artifact_ids);
    }

    public function testItReturnsColumns(): void
    {
        $results = $this->getQueryResults();
        $hash    = md5('date_field');
        self::assertCount(count($this->artifact_ids), $results);
        foreach ($results as $row) {
            self::assertArrayHasKey($hash, $row);
            self::assertArrayHasKey('id', $row);
            self::assertSame($this->expected_values[$row['id']], $row[$hash]);
        }
    }
}
