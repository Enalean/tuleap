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
use Tracker;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

final class TrackersPermissionsDaoOnTrackersTest extends TestIntegrationTestCase
{
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

        $project = $core_builder->buildProject();

        $this->tracker1_id = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 1')->getId();
        $this->tracker2_id = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 2')->getId();
        $this->tracker3_id = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 3')->getId();
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
        $tracker_builder->setSubmitPermission($field1_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setSubmitPermission($field3_id, ProjectUGroup::PROJECT_MEMBERS);
    }

    public function testItRetrieveTrackersViewPermissions(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsViewPermissionOnTrackers([ProjectUGroup::PROJECT_MEMBERS], $this->trackers);

        self::assertCount(2, $results);
        self::assertEqualsCanonicalizing([$this->tracker1_id, $this->tracker3_id], $results);
    }

    public function testItRetrieveTrackersViewPermissionsWithAdmin(): void
    {
        $dao     = new TrackersPermissionsDao();
        $results = $dao->searchUserGroupsViewPermissionOnTrackers([ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], $this->trackers);

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
}
