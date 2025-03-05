<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Heartbeat;

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_ArtifactFactory;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class LatestHeartbeatsCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private LatestHeartbeatsCollector $collector;
    private MockObject|UserManager $user_manager;
    private MockObject|Tracker_ArtifactFactory $factory;
    private MockObject|ExecutionDao $dao;

    protected function setUp(): void
    {
        $this->dao          = $this->createMock(ExecutionDao::class);
        $this->factory      = $this->createMock(Tracker_ArtifactFactory::class);
        $this->user_manager = $this->createMock(UserManager::class);

        $this->collector = new LatestHeartbeatsCollector(
            $this->dao,
            $this->factory,
            $this->user_manager
        );
    }

    public function testItDoesNotCollectAnythingWhenNoTestExecHaveBeenUpdated(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = $this->createMock(\PFUser::class);
        $user->method('getUgroups')->willReturn([]);

        $collection = new HeartbeatsEntryCollection($project, $user);

        $this->dao->method('searchLastTestExecUpdate')->willReturn([]);

        $this->collector->collect($collection);

        $this->assertCount(0, $collection->getLatestEntries());
    }

    public function testItCollectCampaign(): void
    {
        $project = ProjectTestBuilder::aProject()->build();
        $user    = $this->createMock(\PFUser::class);
        $user->method('getUgroups')->willReturn([]);

        $collection = new HeartbeatsEntryCollection($project, $user);

        $row_artifact = [
            'id' => 101,
            'tracker_id' => 1,
            'submitted_by' => 1001,
            'submitted_on' => 123456789,
            'last_update_date' => 123456789,
            'last_updated_by_id' => 101,
            'use_artifact_permissions' => 1,
        ];

        $this->dao->method('searchLastTestExecUpdate')->willReturn([$row_artifact]);

        $color   = TrackerColor::fromName('chrome-silver');
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getColor')->willReturn($color);

        $artifact = $this->createMock(Artifact::class);
        $artifact->method('getLastUpdateDate')->willReturn(123456789);
        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getId')->willReturn(101);
        $artifact->method('getXRef')->willReturn('campaing #101');
        $artifact->method('getTitle')->willReturn('Tuleap 12.1');
        $artifact->method('userCanView')->willReturn(true);

        $this->factory->method('getInstanceFromRow')->willReturn($artifact);

        $this->user_manager->method('getUserById')->willReturn($user);

        $this->collector->collect($collection);

        $this->assertCount(1, $collection->getLatestEntries());
    }
}
