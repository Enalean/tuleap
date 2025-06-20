<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use ProjectUGroup;
use Tuleap\CrossTracker\Tests\CrossTrackerQueryTestBuilder;
use Tuleap\DB\DBFactory;
use Tuleap\Test\Builders\CoreDatabaseBuilder;
use Tuleap\Tracker\Test\Builders\TrackerDatabaseBuilder;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class PermissionsTest extends CrossTrackerFieldTestCase
{
    public function testItGetOnlyArtifactsUserCanSee(): void
    {
        $db              = DBFactory::getMainTuleapDBConnection()->getDB();
        $tracker_builder = new TrackerDatabaseBuilder($db);
        $core_builder    = new CoreDatabaseBuilder($db);

        $project    = $core_builder->buildProject('project_name');
        $project_id = (int) $project->getID();
        $user_1     = $core_builder->buildUser('user_1', 'User 1', 'user_1@example.com');
        $user_2     = $core_builder->buildUser('user_2', 'User 2', 'user_2@example.com');
        $core_builder->addUserToProjectMembers((int) $user_1->getId(), $project_id);
        $core_builder->addUserToProjectMembers((int) $user_1->getId(), $project_id);
        $uuid = $this->addWidgetToProject(1, $project_id);

        $release_tracker = $tracker_builder->buildTracker($project_id, 'Release');
        $tracker_builder->setViewPermissionOnTracker($release_tracker->getId(), Tracker::PERMISSION_SUBMITTER, ProjectUGroup::PROJECT_MEMBERS);

        $user_1_artifact = $tracker_builder->buildArtifact($release_tracker->getId(), 1, (int) $user_1->getId());
        $user_2_artifact = $tracker_builder->buildArtifact($release_tracker->getId(), 1, (int) $user_2->getId());
        $tracker_builder->buildLastChangeset($user_1_artifact);
        $tracker_builder->buildLastChangeset($user_2_artifact);

        $result = $this->getMatchingArtifactIds(
            CrossTrackerQueryTestBuilder::aQuery()->withUUID($uuid)->withTqlQuery("SELECT @id FROM @project = 'self' WHERE @id >= 1")->build(),
            $user_1,
        );
        self::assertCount(1, $result);
        self::assertSame([$user_1_artifact], $result);
    }
}
