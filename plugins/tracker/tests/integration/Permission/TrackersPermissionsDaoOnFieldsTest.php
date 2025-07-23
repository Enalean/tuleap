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
use Tracker_FormElementFactory;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersPermissionsDaoOnFieldsTest extends TestIntegrationTestCase
{
    private int $project_id;
    /**
     * @var non-empty-list<int>
     */
    private array $fields_id;
    private int $field1_id;
    private int $field3_id;

    #[\Override]
    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project          = $core_builder->buildProject('project_name');
        $this->project_id = (int) $project->getID();

        $tracker         = $tracker_builder->buildTracker($this->project_id, 'Release');
        $this->field1_id = $tracker_builder->buildIntField($tracker->getId(), 'field1');
        $field2_id       = $tracker_builder->buildStaticListField($tracker->getId(), 'field2', 'sb');
        $this->field3_id = $tracker_builder->buildDateField($tracker->getId(), 'field3', false);
        $this->fields_id = [$this->field1_id, $field2_id, $this->field3_id];

        $tracker_builder->grantReadPermissionOnField($this->field1_id, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->grantReadPermissionOnField($field2_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantReadPermissionOnField($this->field3_id, ProjectUGroup::PROJECT_MEMBERS);
    }

    public function testItRetrieveFieldsReadPermissions(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsPermissionOnFields([new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_MEMBERS)], $this->fields_id, FieldPermissionType::PERMISSION_READ);

        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$this->field1_id, $this->field3_id], $results);
    }

    public function testItRetrieveFieldsReadPermissionsWithAdmin(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsPermissionOnFields([
            new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_MEMBERS),
            new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_ADMIN),
        ], $this->fields_id, FieldPermissionType::PERMISSION_READ);

        self::assertCount(3, $results);
        self::assertEqualsCanonicalizing($this->fields_id, $results);
    }

    public function testItDoesNotRetrieveFieldFromProjectWhenAdminOfAnotherProject(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $retriever       = TrackersPermissionsRetriever::build();

        $project       = $core_builder->buildProject('project');
        $project_admin = $core_builder->buildProject('project_admin');
        $user          = $core_builder->buildUser('admin', 'Admin', 'admin@example.com');
        $core_builder->addUserToProjectMembers((int) $user->getId(), (int) $project->getID());
        $core_builder->addUserToProjectMembers((int) $user->getId(), (int) $project_admin->getID());
        $core_builder->addUserToProjectAdmins((int) $user->getId(), (int) $project_admin->getID());

        $tracker_1  = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 1');
        $field_1_id = $tracker_builder->buildIntField($tracker_1->getId(), 'int_field');
        $tracker_2  = $tracker_builder->buildTracker((int) $project_admin->getID(), 'Tracker 2');
        $field_2_id = $tracker_builder->buildIntField($tracker_2->getId(), 'int_field');
        $tracker_builder->grantReadPermissionOnField($field_1_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantReadPermissionOnField($field_2_id, ProjectUGroup::PROJECT_MEMBERS);
        $factory = Tracker_FormElementFactory::instance();
        $field_1 = $factory->getFieldById($field_1_id);
        $field_2 = $factory->getFieldById($field_2_id);
        self::assertNotNull($field_1);
        self::assertNotNull($field_2);

        $result = $retriever->retrieveUserPermissionOnFields($user, [$field_1, $field_2], FieldPermissionType::PERMISSION_READ);
        self::assertCount(1, $result->allowed);
        self::assertSame($field_2_id, $result->allowed[0]->getId());
        self::assertCount(1, $result->not_allowed);
        self::assertSame($field_1_id, $result->not_allowed[0]->getId());
    }
}
