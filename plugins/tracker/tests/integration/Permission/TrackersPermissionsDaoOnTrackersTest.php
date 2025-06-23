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
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackersPermissionsDaoOnTrackersTest extends TestIntegrationTestCase
{
    private int $project_id;
    /**
     * @var list<int>
     */
    private array $trackers;
    private int $tracker1_id;
    private int $tracker2_id;
    private int $tracker3_id;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project          = $core_builder->buildProject('project_name');
        $this->project_id = (int) $project->getID();

        $this->tracker1_id = $tracker_builder->buildTracker($this->project_id, 'Tracker 1')->getId();
        $this->tracker2_id = $tracker_builder->buildTracker($this->project_id, 'Tracker 2')->getId();
        $this->tracker3_id = $tracker_builder->buildTracker($this->project_id, 'Tracker 3')->getId();
        $this->trackers    = [$this->tracker1_id, $this->tracker2_id, $this->tracker3_id];
        $tracker_builder->setViewPermissionOnTracker(
            $this->tracker1_id,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS,
        );
        $tracker_builder->setViewPermissionOnTracker(
            $this->tracker2_id,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_ADMIN,
        );
        $tracker_builder->setViewPermissionOnTracker(
            $this->tracker3_id,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS,
        );

        $field1_id = $tracker_builder->buildIntField($this->tracker1_id, 'int_field1');
        $field3_id = $tracker_builder->buildIntField($this->tracker3_id, 'int_field3');
        $tracker_builder->grantSubmitPermissionOnField($field1_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantSubmitPermissionOnField($field3_id, ProjectUGroup::PROJECT_MEMBERS);

        $_SERVER['REQUEST_URI'] = '';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['REQUEST_URI']);
    }

    public function testItRetrieveTrackersViewPermissions(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsViewPermissionOnTrackers([new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_MEMBERS)], $this->trackers);

        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$this->tracker1_id, $this->tracker3_id], $results);
    }

    public function testItRetrieveTrackersViewPermissionsWithAdmin(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsViewPermissionOnTrackers([
            new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_MEMBERS),
            new UserGroupInProject($this->project_id, ProjectUGroup::PROJECT_ADMIN),
        ], $this->trackers);

        self::assertCount(3, $results);
        self::assertEqualsCanonicalizing([$this->tracker1_id, $this->tracker2_id, $this->tracker3_id], $results);
    }

    public function testItRetrieveTrackersSubmitPermissions(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsSubmitPermissionOnTrackers([ProjectUGroup::PROJECT_MEMBERS], $this->trackers);

        self::assertCount(1, $results);
        self::assertEqualsCanonicalizing([$this->tracker3_id], $results);
    }

    public function testItRetrieveTrackersSubmitPermissionsWithAdmin(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsSubmitPermissionOnTrackers([ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], $this->trackers);

        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$this->tracker1_id, $this->tracker3_id], $results);
    }

    public function testItDoesNotRetrieveTrackerViewFromProjectWhenAdminOfAnotherProject(): void
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

        $tracker_1 = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 1');
        $tracker_2 = $tracker_builder->buildTracker((int) $project_admin->getID(), 'Tracker 2');
        $tracker_builder->setViewPermissionOnTracker($tracker_1->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnTracker($tracker_2->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $result = $retriever->retrieveUserPermissionOnTrackers($user, [$tracker_1, $tracker_2], TrackerPermissionType::PERMISSION_VIEW);
        self::assertCount(1, $result->allowed);
        self::assertSame($tracker_2->getId(), $result->allowed[0]->getId());
        self::assertCount(1, $result->not_allowed);
        self::assertSame($tracker_1->getId(), $result->not_allowed[0]->getId());
    }

    public function testItDoesNotRetrieveTrackerSubmitFromProjectWhenAdminOfAnotherProject(): void
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

        $tracker_1 = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 1');
        $tracker_2 = $tracker_builder->buildTracker((int) $project_admin->getID(), 'Tracker 2');
        $tracker_builder->setViewPermissionOnTracker($tracker_1->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnTracker($tracker_2->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $result = $retriever->retrieveUserPermissionOnTrackers($user, [$tracker_1, $tracker_2], TrackerPermissionType::PERMISSION_SUBMIT);
        self::assertCount(1, $result->allowed);
        self::assertSame($tracker_2->getId(), $result->allowed[0]->getId());
        self::assertCount(1, $result->not_allowed);
        self::assertSame($tracker_1->getId(), $result->not_allowed[0]->getId());
    }
}
