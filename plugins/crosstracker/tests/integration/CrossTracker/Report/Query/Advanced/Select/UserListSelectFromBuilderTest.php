<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);
namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

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
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilderVisitor;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class UserListSelectFromBuilderTest extends CrossTrackerFieldTestCase
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
     * @var array<int, array<string, int|string>>
     */
    private array $expected_values;
    private PFUser $alice;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project        = $core_builder->buildProject();
        $project_id     = (int) $project->getID();
        $project_member = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $project_admin  = $core_builder->buildUser('project_admin', 'Project Admin', 'project_admin@example.com');
        $core_builder->addUserToProjectMembers((int) $project_member->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $project_admin->getId(), $project_id);
        $core_builder->addUserToProjectAdmins((int) $project_admin->getId(), $project_id);

        $this->alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob         = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $this->alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $this->trackers  = [$release_tracker, $sprint_tracker];

        $release_user_static_list_field_id = $tracker_builder->buildUserListField(
            $release_tracker->getId(),
            'user_list_field',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
        );

        $sprint_user_list_field_id = $tracker_builder->buildUserListField(
            $sprint_tracker->getId(),
            'user_list_field',
            Tracker_FormElementFactory::FIELD_OPEN_LIST_TYPE
        );

        $sprint_bind_user_open_ids = $tracker_builder->buildValuesForStaticOpenListField(
            $sprint_user_list_field_id,
            [$bob->getEmail()],
        );

        $tracker_builder->setReadPermission(
            $release_user_static_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setReadPermission(
            $sprint_user_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id            = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_static_list_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_open_list_id    = $tracker_builder->buildArtifact($sprint_tracker->getId());
        $this->artifact_ids                   = [
            $release_artifact_empty_id,
            $release_artifact_with_static_list_id,
            $sprint_artifact_with_open_list_id,
            $sprint_artifact_with_open_list_id,
        ];

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_list_changeset     = $tracker_builder->buildLastChangeset(
            $release_artifact_with_static_list_id
        );
        $sprint_artifact_with_open_list_changeset = $tracker_builder->buildLastChangeset(
            $sprint_artifact_with_open_list_id
        );

        $hash                  = md5('user_list_field');
        $this->expected_values = [
            $release_artifact_with_static_list_id => ["user_$hash" => $this->alice->getId()],
            $sprint_artifact_with_open_list_id    => ["user_open_list_value_$hash" => $bob->getEmail(), "user_$hash" => $project_admin->getId() ],
        ];

        $tracker_builder->buildListValue(
            $release_artifact_with_list_changeset,
            $release_user_static_list_field_id,
            (int) $this->alice->getId()
        );

        $tracker_builder->buildListValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_user_list_field_id,
            (int) $project_admin->getId(),
        );

        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_user_list_field_id,
            $sprint_bind_user_open_ids[$bob->getEmail()],
            true
        );

        $this->builder = new SelectBuilderVisitor(
            new FieldSelectFromBuilder(
                Tracker_FormElementFactory::instance(),
                new FieldTypeRetrieverWrapper(Tracker_FormElementFactory::instance()),
                TrackersPermissionsRetriever::build(),
                new DateSelectFromBuilder(),
                new TextSelectFromBuilder(),
                new NumericSelectFromBuilder(),
                new StaticListSelectFromBuilder(),
                new UGroupListSelectFromBuilder(),
                new UserListSelectFromBuilder()
            )
        );
        $this->dao     = new CrossTrackerExpertQueryReportDao();
    }

    private function getQueryResults(): array
    {
        $select_from = $this->builder->buildSelectFrom(
            [new Field('user_list_field')],
            $this->trackers,
            $this->alice,
        );

        return $this->dao->searchArtifactsColumnsMatchingIds($select_from, $this->artifact_ids);
    }

    public function testItReturnsColumns(): void
    {
        $results         = $this->getQueryResults();
        $hash            = md5('user_list_field');
        $list_field_hash = 'user_' . $hash;
        $open_field_hash = 'user_open_list_value_' . $hash;

        self::assertCount(count($this->artifact_ids), $results);

        foreach ($results as $row) {
            self::assertArrayHasKey($list_field_hash, $row);
            self::assertArrayHasKey($open_field_hash, $row);
            self::assertArrayHasKey('id', $row);
            $this->assertListValueCase($row, $list_field_hash, $open_field_hash);
            $this->assertOpenValueCase($row, $list_field_hash, $open_field_hash);
            $this->assertNullValueCase($row, $list_field_hash, $open_field_hash);
        }
    }

    private function assertNullValueCase(array $row, string $list_field_hash, string $open_field_hash): void
    {
        if ($row[$list_field_hash] === null && $row[$open_field_hash] === null) {
            self::assertNull($row[$list_field_hash]);
            self::assertNull($row[$open_field_hash]);
        }
    }

    private function assertListValueCase(array $row, string $list_field_hash, string $open_field_hash): void
    {
        if ($row[$list_field_hash] !== null && $row[$open_field_hash] === null) {
            self::assertSame($this->expected_values[$row['id']][$list_field_hash], $row[$list_field_hash]);
            self::assertNull($row[$open_field_hash]);
        }
    }

    private function assertOpenValueCase(array $row, string $list_field_hash, string $open_field_hash): void
    {
        if ($row[$list_field_hash] === null && $row[$open_field_hash] !== null) {
            self::assertNull($row[$list_field_hash]);
            self::assertSame($this->expected_values[$row['id']][$open_field_hash], $row[$open_field_hash]);
        }
    }
}
