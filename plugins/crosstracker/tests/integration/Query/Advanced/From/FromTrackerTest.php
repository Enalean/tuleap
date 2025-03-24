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

namespace Tuleap\CrossTracker\Query\Advanced\From;

use PFUser;
use ProjectUGroup;
use Tracker;
use Tuleap\CrossTracker\Query\Advanced\CrossTrackerFieldTestCase;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TrackerRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerQueryContentRepresentation;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\DB\UUID;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FromTrackerTest extends CrossTrackerFieldTestCase
{
    private UUID $uuid;
    private PFUser $user;

    protected function setUp(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $core_builder    = new CoreDatabaseBuilder($db);
        $tracker_builder = new TrackerDatabaseBuilder($db);

        $project_1    = $core_builder->buildProject('Project 1');
        $project_1_id = (int) $project_1->getID();
        $project_2    = $core_builder->buildProject('Project 2');
        $project_2_id = (int) $project_2->getID();
        $this->user   = $core_builder->buildUser('bob', 'Bob', 'bob@example.com');
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_1_id);
        $core_builder->addUserToProjectMembers((int) $this->user->getId(), $project_2_id);

        $cat_id = $core_builder->buildTroveCat('foo', 'foo');
        $core_builder->addTroveCatToProject($cat_id, $project_1_id);
        $core_builder->addTroveCatToProject($cat_id, $project_2_id);

        $tracker_1 = $tracker_builder->buildTracker($project_1_id, 'Release');
        $tracker_2 = $tracker_builder->buildTracker($project_1_id, 'Sprint');
        $tracker_3 = $tracker_builder->buildTracker($project_2_id, 'Sprint');
        $tracker_builder->setViewPermissionOnTracker($tracker_1->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($tracker_2->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);
        $tracker_builder->setViewPermissionOnTracker($tracker_3->getId(), Tracker::PERMISSION_FULL, ProjectUGroup::PROJECT_MEMBERS);

        $tracker_builder->buildLastChangeset($tracker_builder->buildArtifact($tracker_1->getId()));
        $tracker_builder->buildLastChangeset($tracker_builder->buildArtifact($tracker_2->getId()));
        $tracker_builder->buildLastChangeset($tracker_builder->buildArtifact($tracker_3->getId()));

        $this->uuid = $this->addWidgetToProject(1, $project_1_id);
    }

    /**
     * @param list<string> $expected
     */
    private function assertItContainsTrackers(array $expected, CrossTrackerQueryContentRepresentation $result): void
    {
        $found = [];
        foreach ($result->artifacts as $artifact) {
            self::assertArrayHasKey('@tracker.name', $artifact);
            $name = $artifact['@tracker.name'];
            self::assertInstanceOf(TrackerRepresentation::class, $name);
            $found[] = $name->name;
        }
        self::assertEqualsCanonicalizing($expected, $found);
    }

    public function testTrackerNameEqual(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                 ->withUUID($this->uuid)->withTqlQuery('SELECT @tracker.name FROM @tracker.name = "release" WHERE @id >= 1')->build(),
            $this->user,
        );
        $this->assertItContainsTrackers(['Release'], $result);
    }

    public function testTrackerNameIn(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                 ->withUUID($this->uuid)->withTqlQuery('SELECT @tracker.name FROM @tracker.name IN("release", "sprint") WHERE @id >= 1')->build(),
            $this->user,
        );
        $this->assertItContainsTrackers(['Release', 'Sprint'], $result);
    }

    public function testTrackerNameEqualWithProject(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @tracker.name FROM @tracker.name = "sprint" AND @project.category = "foo" WHERE @id >= 1',
                )->build(),
            $this->user,
        );
        $this->assertItContainsTrackers(['Sprint', 'Sprint'], $result);
    }

    public function testTrackerNameInWithProject(): void
    {
        $result = $this->getQueryResults(
            CrossTrackerQueryTestBuilder::aQuery()
                ->withUUID($this->uuid)->withTqlQuery(
                    'SELECT @tracker.name FROM @tracker.name IN("sprint", "release") AND @project.category = "foo" WHERE @id >= 1',
                )->build(),
            $this->user,
        );
        $this->assertItContainsTrackers(['Sprint', 'Sprint', 'Release'], $result);
    }
}
