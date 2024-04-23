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

final class TrackersPermissionsDaoOnArtifactsTest extends TestIntegrationTestCase
{
    private TrackersPermissionsDao $dao;
    private int $artifact_open;
    private int $artifact_open_member;
    private int $artifact_open_admin;
    private int $artifact_close;
    /**
     * @var list<int>
     */
    private array $artifacts;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $this->dao       = new TrackersPermissionsDao();

        $project = $core_builder->buildProject();

        $tracker_open_id       = $tracker_builder->buildTracker((int) $project->getID(), 'Open Tracker')->getId();
        $tracker_restricted_id = $tracker_builder->buildTracker((int) $project->getID(), 'Restricted Tracker')->getId();
        $tracker_builder->setViewPermissionOnTracker(
            $tracker_open_id,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setViewPermissionOnTracker(
            $tracker_restricted_id,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_ADMIN
        );

        $this->artifact_open        = $tracker_builder->buildArtifact($tracker_open_id);
        $this->artifact_open_member = $tracker_builder->buildArtifact($tracker_open_id);
        $this->artifact_open_admin  = $tracker_builder->buildArtifact($tracker_open_id);
        $this->artifact_close       = $tracker_builder->buildArtifact($tracker_restricted_id);
        $artifact_very_closed       = $tracker_builder->buildArtifact($tracker_restricted_id);
        $tracker_builder->setViewPermissionOnArtifact($this->artifact_open_member, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnArtifact($this->artifact_open_admin, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnArtifact($artifact_very_closed, ProjectUGroup::WIKI_ADMIN);

        $this->artifacts = [
            $this->artifact_open,
            $this->artifact_open_member,
            $this->artifact_open_admin,
            $this->artifact_close,
            $artifact_very_closed,
        ];
    }

    public function testProjectMemberPermission(): void
    {
        $result = $this->dao->searchUserGroupsViewPermissionOnArtifacts([ProjectUGroup::PROJECT_MEMBERS], $this->artifacts);
        self::assertEqualsCanonicalizing([$this->artifact_open, $this->artifact_open_member], $result);
    }

    public function testProjectAdminPermission(): void
    {
        $result = $this->dao->searchUserGroupsViewPermissionOnArtifacts([ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN], $this->artifacts);
        self::assertEqualsCanonicalizing([
            $this->artifact_open,
            $this->artifact_open_member,
            $this->artifact_open_admin,
            $this->artifact_close,
        ], $result);
    }
}
