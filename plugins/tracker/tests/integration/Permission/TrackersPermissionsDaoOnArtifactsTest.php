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
use Tracker_ArtifactFactory;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Test\PHPUnit\TestIntegrationTestCase;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
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
        $tracker_builder->grantViewPermissionOnArtifact($this->artifact_open_member, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->grantViewPermissionOnArtifact($this->artifact_closed, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantViewPermissionOnArtifact($this->artifact_closed_2, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantViewPermissionOnArtifact($artifact_very_closed, ProjectUGroup::WIKI_ADMIN);

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

    public function testItDoesNotRetrieveArtifactFromProjectWhenAdminOfAnotherProject(): void
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

        $tracker_1     = $tracker_builder->buildTracker((int) $project->getID(), 'Tracker 1');
        $artifact_1_id = $tracker_builder->buildArtifact($tracker_1->getId());
        $tracker_2     = $tracker_builder->buildTracker((int) $project_admin->getID(), 'Tracker 2');
        $artifact_2_id = $tracker_builder->buildArtifact($tracker_2->getId());
        $tracker_builder->grantViewPermissionOnArtifact($artifact_1_id, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->grantViewPermissionOnArtifact($artifact_2_id, ProjectUGroup::PROJECT_MEMBERS);
        $factory    = Tracker_ArtifactFactory::instance();
        $artifact_1 = $factory->getArtifactById($artifact_1_id);
        $artifact_2 = $factory->getArtifactById($artifact_2_id);
        self::assertNotNull($artifact_1);
        self::assertNotNull($artifact_2);

        $result = $retriever->retrieveUserPermissionOnArtifacts($user, [$artifact_1, $artifact_2], ArtifactPermissionType::PERMISSION_VIEW);
        self::assertCount(1, $result->allowed);
        self::assertSame($artifact_2_id, $result->allowed[0]->getId());
        self::assertCount(1, $result->not_allowed);
        self::assertSame($artifact_1_id, $result->not_allowed[0]->getId());
    }
}
