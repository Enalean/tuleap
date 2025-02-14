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

namespace Tuleap\CrossTracker\Report\Query\Advanced\Select;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerQuery;
use Tuleap\CrossTracker\Report\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Report\Query\Advanced\ResultBuilder\Representations\UserRepresentation;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserHelper;

final class SubmittedBySelectBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /**
     * @var array<int, UserRepresentation>
     */
    private array $expected_results;

    public function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);

        $alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob   = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);
        $this->uuid = $this->addReportToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_submitted_by_field_id = $tracker_builder->buildSubmittedByField($release_tracker->getId());
        $sprint_submitted_by_field_id  = $tracker_builder->buildSubmittedByField($sprint_tracker->getId());

        $tracker_builder->grantReadPermissionOnField(
            $release_submitted_by_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );
        $tracker_builder->grantReadPermissionOnField(
            $sprint_submitted_by_field_id,
            ProjectUGroup::PROJECT_MEMBERS
        );

        $release_artifact_id = $tracker_builder->buildArtifact($release_tracker->getId(), 123456789, (int) $alice->getId());
        $sprint_artifact_id  = $tracker_builder->buildArtifact($sprint_tracker->getId(), 123456789, (int) $bob->getId());

        $tracker_builder->buildLastChangeset($release_artifact_id);
        $tracker_builder->buildLastChangeset($sprint_artifact_id);

        $helper                 = UserHelper::instance();
        $this->expected_results = [
            $release_artifact_id => UserRepresentation::fromPFUser($alice, $helper),
            $sprint_artifact_id  => UserRepresentation::fromPFUser($bob, $helper),
        ];
    }

    public function testItReturnsColumns(): void
    {
        $result = $this->getQueryResults(
            new CrossTrackerQuery(
                $this->uuid,
                "SELECT @submitted_by FROM @project = 'self' WHERE @submitted_by = 'bob' OR @submitted_by != 'bob'",
                '',
                '',
                1,
            ),
            $this->user,
        );
        self::assertSame(2, $result->getTotalSize());
        self::assertCount(2, $result->selected);
        self::assertSame('@submitted_by', $result->selected[1]->name);
        self::assertSame('user', $result->selected[1]->type);
        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@submitted_by', $artifact);
            $value = $artifact['@submitted_by'];
            self::assertInstanceOf(UserRepresentation::class, $value);
            $values[] = $value;
        }
        self::assertEqualsCanonicalizing(
            array_values($this->expected_results),
            $values
        );
    }
}
