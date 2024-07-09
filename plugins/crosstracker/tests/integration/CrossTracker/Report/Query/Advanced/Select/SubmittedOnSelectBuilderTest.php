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

use DateTimeImmutable;
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
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UGroupList\UGroupListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Field\UserList\UserListSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\MetadataSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class SubmittedOnSelectBuilderTest extends CrossTrackerFieldTestCase
{
    private PFUser $user;
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
    private array $expected_results;
    private CrossTrackerExpertQueryReportDao $dao;
    private SelectBuilderVisitor $builder;

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

        $release_submitted_on_field_id = $tracker_builder->buildSubmittedOnField($release_tracker->getId());
        $sprint_submitted_on_field_id  = $tracker_builder->buildSubmittedOnField($sprint_tracker->getId());

        $tracker_builder->setReadPermission(
            $release_submitted_on_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_submitted_on_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_date        = (new DateTimeImmutable('2024-07-05 17:03'))->getTimestamp();
        $sprint_date         = (new DateTimeImmutable('2024-12-24 02:58'))->getTimestamp();
        $release_artifact_id = $tracker_builder->buildArtifact($release_tracker->getId(), $release_date);
        $sprint_artifact_id  = $tracker_builder->buildArtifact($sprint_tracker->getId(), $sprint_date);
        $this->artifact_ids  = [$release_artifact_id, $sprint_artifact_id];

        $tracker_builder->buildLastChangeset($release_artifact_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_id);

        $this->expected_results = [
            $release_artifact_id => $release_date,
            $sprint_artifact_id  => $sprint_date,
        ];

        $this->dao            = new CrossTrackerExpertQueryReportDao();
        $form_element_factory = Tracker_FormElementFactory::instance();
        $this->builder        = new SelectBuilderVisitor(
            new FieldSelectFromBuilder(
                $form_element_factory,
                new FieldTypeRetrieverWrapper($form_element_factory),
                TrackersPermissionsRetriever::build(),
                new DateSelectFromBuilder(),
                new TextSelectFromBuilder(),
                new NumericSelectFromBuilder(),
                new StaticListSelectFromBuilder(),
                new UGroupListSelectFromBuilder(),
                new UserListSelectFromBuilder(),
            ),
            new MetadataSelectFromBuilder(
                new TitleSelectFromBuilder(),
                new DescriptionSelectFromBuilder(),
                new StatusSelectFromBuilder(),
                new AssignedToSelectFromBuilder(),
            ),
        );
    }

    public function testItReturnsColumns(): void
    {
        $fragments = $this->builder->buildSelectFrom([new Metadata('submitted_on')], $this->trackers, $this->user);
        $results   = $this->dao->searchArtifactsColumnsMatchingIds($fragments, $this->artifact_ids);

        self::assertCount(2, $results);
        $values = [];
        foreach ($results as $result) {
            self::assertArrayHasKey('id', $result);
            self::assertArrayHasKey('@submitted_on', $result);
            $values[$result['id']] = $result['@submitted_on'];
        }
        self::assertEqualsCanonicalizing($values, $this->expected_results);
    }
}
