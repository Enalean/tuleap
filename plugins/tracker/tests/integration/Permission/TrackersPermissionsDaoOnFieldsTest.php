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

namespace Tuleap\Tracker\Permission;

use ProjectUGroup;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class TrackersPermissionsDaoOnFieldsTest extends TestIntegrationTestCase
{
    /**
     * @var non-empty-list<int>
     */
    private array $fields_id;
    private int $field1_id;
    private int $field3_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project = $core_builder->buildProject('project_name');

        $tracker         = $tracker_builder->buildTracker((int) $project->getID(), 'Release');
        $this->field1_id = $tracker_builder->buildIntField($tracker->getId(), 'field1');
        $field2_id       = $tracker_builder->buildStaticListField($tracker->getId(), 'field2', 'sb');
        $this->field3_id = $tracker_builder->buildDateField($tracker->getId(), 'field3', false);
        $this->fields_id = [$this->field1_id, $field2_id, $this->field3_id];

        $tracker_builder->setReadPermission($this->field1_id, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setReadPermission($field2_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setReadPermission($this->field3_id, ProjectUGroup::PROJECT_MEMBERS);
    }

    public function testItRetrieveFieldsReadPermissions(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsPermissionOnFields([ProjectUGroup::PROJECT_MEMBERS], $this->fields_id, FieldPermissionType::PERMISSION_READ->value);

        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$this->field1_id, $this->field3_id], $results);
    }

    public function testItRetrieveFieldsReadPermissionsWithAdmin(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsPermissionOnFields([ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], $this->fields_id, FieldPermissionType::PERMISSION_READ->value);

        self::assertCount(3, $results);
        self::assertEqualsCanonicalizing($this->fields_id, $results);
    }
}
