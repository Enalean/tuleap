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

use BaseLanguageFactory;
use PFUser;
use ProjectUGroup;
use Tracker;
use Tracker_FormElementFactory;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UGroupListRepresentation;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UGroupListValueRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class UGroupListSelectFromBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    /**
     * @var array<int, UGroupListValueRepresentation[]>
     */
    private array $expected_values;
    private PFUser $user;

    public function setUp(): void
    {
        $GLOBALS['Language'] = (new BaseLanguageFactory())->getBaseLanguage('en_US');

        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_ugroup_static_list_field_id = $tracker_builder->buildUserGroupListField(
            $release_tracker->getId(),
            'ugroup_list_field',
            Tracker_FormElementFactory::FIELD_SELECT_BOX_TYPE
        );

        $static_ugroup_id = $core_builder->buildStaticUserGroup($project_id, 'Bagheera');
        $core_builder->buildStaticUserGroup($project_id, 'Rancho');

        $release_bind_ids = $tracker_builder->buildValuesForUserGroupListField(
            $release_ugroup_static_list_field_id,
            [ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN, $static_ugroup_id]
        );

        $sprint_ugroup_list_field_id = $tracker_builder->buildUserGroupListField(
            $sprint_tracker->getId(),
            'ugroup_list_field',
            Tracker_FormElementFactory::FIELD_OPEN_LIST_TYPE
        );

        $sprint_bind_ugroup_list_ids = $tracker_builder->buildValuesForUserGroupListField(
            $sprint_ugroup_list_field_id,
            [$static_ugroup_id, ProjectUGroup::PROJECT_MEMBERS],
        );

        $sprint_bind_ugroup_open_ids = $tracker_builder->buildValuesForStaticOpenListField(
            $sprint_ugroup_list_field_id,
            ['Rancho'],
        );

        $tracker_builder->grantReadPermissionOnField(
            $release_ugroup_static_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_ugroup_list_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_empty_id            = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_with_static_list_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_with_open_list_id    = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_empty_id);
        $release_artifact_with_list_changeset     = $tracker_builder->buildLastChangeset($release_artifact_with_static_list_id);
        $sprint_artifact_with_open_list_changeset = $tracker_builder->buildLastChangeset($sprint_artifact_with_open_list_id);

        $this->expected_values = [
            $release_artifact_empty_id            => [],
            $release_artifact_with_static_list_id => [new UGroupListValueRepresentation('Project members')],
            $sprint_artifact_with_open_list_id    => [
                new UGroupListValueRepresentation('Rancho'),
                new UGroupListValueRepresentation('Bagheera'),
            ],
        ];

        $tracker_builder->buildListValue(
            $release_artifact_with_list_changeset,
            $release_ugroup_static_list_field_id,
            $release_bind_ids[ProjectUGroup::PROJECT_MEMBERS]
        );
        $tracker_builder->buildListValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_ugroup_list_field_id,
            $sprint_bind_ugroup_list_ids[$static_ugroup_id]
        );
        $tracker_builder->buildOpenValue(
            $sprint_artifact_with_open_list_changeset,
            $sprint_ugroup_list_field_id,
            $sprint_bind_ugroup_open_ids['Rancho'],
            true
        );
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT ugroup_list_field FROM @project = 'self' WHERE ugroup_list_field = '' OR ugroup_list_field != ''",
                '',
                '',
                1,
            ),
            $this->user,
        );

        self::assertSame(3, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('ugroup_list_field', $result->selected[1]->name);
        self::assertSame('list_user_group', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('ugroup_list_field', $artifact);
            $value = $artifact['ugroup_list_field'];
            self::assertInstanceOf(UGroupListRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertEqualsCanonicalizing(array_values($this->expected_values), $values);
    }
}
