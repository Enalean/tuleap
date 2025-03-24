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

namespace Tuleap\CrossTracker\Query\Advanced\OrderBy;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\NumericResultRepresentation;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use UserHelper;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LastUpdateByOrderByBuilderTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;
    /** @var list<int> */
    private array $result_descending;
    /** @var list<int> */
    private array $result_ascending;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $this->user = $core_builder->buildUser('project_member', 'Project Member', 'project_member@example.com');
        $this->user->setPreference(PFUser::PREFERENCE_NAME_DISPLAY_USERS, (string) UserHelper::PREFERENCES_LOGIN);
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_id);
        $this->uuid = $this->addWidgetToProject(1, $project_id);

        $alice = $core_builder->buildUser('alice', 'Alice', 'alice@example.com');
        $bob   = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $alice->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $bob->getId(), $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $sprint_tracker  = $tracker_builder->buildTracker($project_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($sprint_tracker->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $release_artifact_1_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $release_artifact_2_id = $tracker_builder->buildArtifact($release_tracker->getId());
        $sprint_artifact_3_id  = $tracker_builder->buildArtifact($sprint_tracker->getId());

        $tracker_builder->buildLastChangeset($release_artifact_1_id, 0, (int) $this->user->getId());
        $tracker_builder->buildLastChangeset($release_artifact_2_id, 0, (int) $alice->getId());
        $tracker_builder->buildLastChangeset($sprint_artifact_3_id, 0, (int) $bob->getId());

        $this->result_descending = [$release_artifact_1_id, $sprint_artifact_3_id, $release_artifact_2_id];
        $this->result_ascending  = [$release_artifact_2_id, $sprint_artifact_3_id, $release_artifact_1_id];
    }

    public function testLastUpdateByDescending(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                 ->withUUID($this->uuid)->withTqlQuery('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY @last_update_by DESC')->build(),
            $this->user,
        );

        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@id', $artifact);
            $value = $artifact['@id'];
            self::assertInstanceOf(NumericResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertSame($this->result_descending, $values);
    }

    public function testLastUpdateByAscending(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                 ->withUUID($this->uuid)->withTqlQuery('SELECT @id FROM @project = "self" WHERE @id >= 1 ORDER BY @last_update_by ASC')->build(),
            $this->user,
        );

        $values = [];
        foreach ($result->artifacts as $artifact) {
            self::assertCount(2, $artifact);
            self::assertArrayHasKey('@id', $artifact);
            $value = $artifact['@id'];
            self::assertInstanceOf(NumericResultRepresentation::class, $value);
            $values[] = $value->value;
        }
        self::assertSame($this->result_ascending, $values);
    }
}
