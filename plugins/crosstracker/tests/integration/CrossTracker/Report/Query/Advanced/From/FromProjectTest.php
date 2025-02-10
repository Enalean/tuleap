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

namespace Tuleap\CrossTracker\Report\Query\Advanced\From;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\TrackerRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerReportContentRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserManager;

final class FromProjectTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid_1;
    private UUID $uuid_2;
    private PFUser $user_member;
    private PFUser $user_admin;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project_1         = $core_builder->buildProject('project_1');
        $project_1_id      = (int) $project_1->getId();
        $project_2         = $core_builder->buildProject('project_2');
        $project_2_id      = (int) $project_2->getId();
        $project_3         = $core_builder->buildProject('project_3');
        $project_3_id      = (int) $project_3->getId();
        $this->user_member = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $this->user_admin  = $core_builder->buildUser('admin', 'Admin', 'admin@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user_member->getId(), $project_1_id);
        $core_builder->addUserToProjectMembers((int) $this->user_member->getId(), $project_2_id);
        $core_builder->addUserToProjectMembers((int) $this->user_admin->getId(), $project_1_id);
        $core_builder->addUserToProjectAdmins((int) $this->user_admin->getId(), $project_1_id);
        $core_builder->addUserToProjectMembers((int) $this->user_admin->getId(), $project_2_id);
        $core_builder->addUserToProjectAdmins((int) $this->user_admin->getId(), $project_2_id);
        $core_builder->addUserToProjectMembers((int) $this->user_admin->getId(), $project_3_id);

        /**
         * type
         *   |-> foo
         *   `-> bar
         */
        $core_builder->buildTroveCat('Type', 'Type');
        $foo_id = $core_builder->buildTroveCat('Foo', 'Type :: Foo');
        $bar_id = $core_builder->buildTroveCat('Bar', 'Type :: Bar');
        $core_builder->addTroveCatToProject($foo_id, $project_1_id);
        $core_builder->addTroveCatToProject($bar_id, $project_2_id);

        $tracker_1  = $tracker_builder->buildTracker($project_1_id, 'Tracker 1');
        $tracker_11 = $tracker_builder->buildTracker($project_1_id, 'Tracker 1.1');
        $tracker_2  = $tracker_builder->buildTracker($project_2_id, 'Tracker 2');
        $tracker_3  = $tracker_builder->buildTracker($project_3_id, 'Tracker 3');
        $tracker_builder->setViewPermissionOnTracker($tracker_1->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($tracker_11->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_ADMIN);
        $tracker_builder->setViewPermissionOnTracker($tracker_2->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $artifact_1 = $tracker_builder->buildArtifact($tracker_1->getId());
        $tracker_builder->buildLastChangeset($artifact_1);
        $artifact_11 = $tracker_builder->buildArtifact($tracker_11->getId());
        $tracker_builder->buildLastChangeset($artifact_11);
        $artifact_2 = $tracker_builder->buildArtifact($tracker_2->getId());
        $tracker_builder->buildLastChangeset($artifact_2);
        $artifact_3 = $tracker_builder->buildArtifact($tracker_3->getId());
        $tracker_builder->buildLastChangeset($artifact_3);

        $this->uuid_1 = $this->addReportToProject(1, $project_1_id);
        $this->uuid_2 = $this->addReportToProject(2, $project_2_id);
        $this->addReportToProject(3, $project_3_id);
    }

    /**
     * @param list<string> $expected
     */
    private function assertItContainsTrackers(array $expected, CrossTrackerReportContentRepresentation $result): void
    {
        $found = [];
        foreach ($result->artifacts as $artifact) {
            self::assertArrayHasKey('@tracker.name', $artifact);
            $name = $artifact['@tracker.name'];
            self::assertInstanceOf(TrackerRepresentation::class, $name);
            if (! in_array($name->name, $found, true)) {
                $found[] = $name->name;
            }
        }
        self::assertEqualsCanonicalizing($expected, $found);
    }

    public function testItGetTrackerFromProjectSelf(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project = "self" WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1'], $result);

        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_2, 'SELECT @tracker.name FROM @project = "self" WHERE @id >= 1', '', '', 2),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 2'], $result);
    }

    public function testItGetTrackerFromMyProjects(): void
    {
        $user_manager = $this->createPartialMock(UserManager::class, ['getCurrentUser']);
        $user_manager->method('getCurrentUser')->willReturn($this->user_member);
        UserManager::setInstance($user_manager);

        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project = MY_PROJECTS() WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1', 'Tracker 2'], $result);
    }

    public function testPermissionsProjectSelf(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project = "self" WHERE @id >= 1', '', '', 1),
            $this->user_admin,
        );
        $this->assertItContainsTrackers(['Tracker 1', 'Tracker 1.1'], $result);
    }

    public function testProjectCategoryEqual(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project.category = "Type::Foo" WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1'], $result);
    }

    public function testProjectCategoryEqualLike(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project.category = "Type" WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1', 'Tracker 2'], $result);
    }

    public function testProjectCategoryIn(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project.category IN("Type::Foo", "Type::Bar") WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1', 'Tracker 2'], $result);
    }

    public function testProjectNameEqual(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project.name = "project_2" WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 2'], $result);
    }

    public function testProjectNameIn(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery($this->uuid_1, 'SELECT @tracker.name FROM @project.name IN("project_1", "project_2") WHERE @id >= 1', '', '', 1),
            $this->user_member,
        );
        $this->assertItContainsTrackers(['Tracker 1', 'Tracker 2'], $result);
    }
}
