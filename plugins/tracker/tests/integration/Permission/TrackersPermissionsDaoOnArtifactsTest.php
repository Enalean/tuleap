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
    private int $artifact_closed;
    private int $artifact_open_2;
    private int $artifact_closed_2;
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

        $project = $core_builder->buildProject('project_name');

        $story_tracker = $tracker_builder->buildTracker((int) $project->getID(), 'Story Tracker')->getId();
        $task_tracker  = $tracker_builder->buildTracker((int) $project->getID(), 'Task Tracker')->getId();
        $tracker_builder->setViewPermissionOnTracker(
            $story_tracker,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->setViewPermissionOnTracker(
            $task_tracker,
            Tracker::PERMISSION_FULL,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $this->artifact_open        = $tracker_builder->buildArtifact($story_tracker);
        $this->artifact_open_member = $tracker_builder->buildArtifact($story_tracker);
        $this->artifact_closed      = $tracker_builder->buildArtifact($story_tracker);
        $this->artifact_open_2      = $tracker_builder->buildArtifact($task_tracker);
        $this->artifact_closed_2    = $tracker_builder->buildArtifact($task_tracker);
        $artifact_very_closed       = $tracker_builder->buildArtifact($task_tracker);
        $tracker_builder->setViewPermissionOnArtifact($this->artifact_open_member, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnArtifact($this->artifact_closed, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnArtifact($this->artifact_closed_2, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnArtifact($artifact_very_closed, ProjectUGroup::WIKI_ADMIN);

        $this->artifacts = [
            $this->artifact_open,
            $this->artifact_open_member,
            $this->artifact_closed,
            $this->artifact_open_2,
            $this->artifact_closed_2,
            $artifact_very_closed,
        ];
    }

    public function testProjectMemberPermission(): void
    {
        $result = $this->dao->searchUserGroupsViewPermissionOnArtifacts([ProjectUGroup::PROJECT_MEMBERS], $this->artifacts);
        self::assertEqualsCanonicalizing([
            $this->artifact_open,
            $this->artifact_open_member,
            $this->artifact_open_2,
        ], $result);
    }

    public function testProjectAdminPermission(): void
    {
        $result = $this->dao->searchUserGroupsViewPermissionOnArtifacts(
            [ProjectUGroup::PROJECT_MEMBERS, ProjectUGroup::PROJECT_ADMIN],
            $this->artifacts
        );
        self::assertEqualsCanonicalizing([
            $this->artifact_open,
            $this->artifact_open_member,
            $this->artifact_closed,
            $this->artifact_open_2,
            $this->artifact_closed_2,
        ], $result);
    }
}
