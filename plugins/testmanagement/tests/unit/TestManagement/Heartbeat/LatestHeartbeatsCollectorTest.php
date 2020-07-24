<?php
/*
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_ArtifactFactory;
use Tuleap\Glyph\Glyph;
use Tuleap\Glyph\GlyphFinder;
use Tuleap\Project\HeartbeatsEntryCollection;
use Tuleap\TestManagement\Campaign\Execution\ExecutionDao;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\TrackerColor;
use UserHelper;
use UserManager;

final class LatestHeartbeatsCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var LatestHeartbeatsCollector
     */
    private $collector;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserHelper
     */
    private $user_helper;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|GlyphFinder
     */
    private $glyph_finder;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_ArtifactFactory
     */
    private $factory;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ExecutionDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao          = \Mockery::mock(ExecutionDao::class);
        $this->factory      = \Mockery::mock(Tracker_ArtifactFactory::class);
        $this->glyph_finder = \Mockery::mock(GlyphFinder::class);
        $this->user_helper  = \Mockery::mock(UserHelper::class);
        $this->user_manager = \Mockery::mock(UserManager::class);

        $this->collector = new LatestHeartbeatsCollector(
            $this->dao,
            $this->factory,
            $this->glyph_finder,
            $this->user_helper,
            $this->user_manager
        );
    }

    public function testItDoesNotCollectAnythingWhenNoTestExecHaveBeenUpdated(): void
    {
        $project = \Project::buildForTest();
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUgroups')->once()->andReturn([]);

        $collection = new HeartbeatsEntryCollection($project, $user);

        $this->dao->shouldReceive('searchLastTestExecUpdate')->once()->andReturn([]);

        $this->collector->collect($collection);

        $this->assertCount(0, $collection->getLatestEntries());
    }

    public function testItCollectCampaign(): void
    {
        $project = \Project::buildForTest();
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getUgroups')->once()->andReturn([]);

        $collection = new HeartbeatsEntryCollection($project, $user);

        $row_artifact = [
           'id' => 101,
           'tracker_id' => 1,
           'submitted_by' => 1001,
           'submitted_on' => 123456789,
           'last_update_date' => 123456789,
           'last_updated_by_id' => 101,
           'use_artifact_permissions' => 1
        ];

        $this->dao->shouldReceive('searchLastTestExecUpdate')->once()->andReturn([$row_artifact]);

        $color = TrackerColor::fromName('chrome-silver');
        $tracker = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive('getColor')->andReturn($color);

        $artifact = \Mockery::mock(Artifact::class);
        $artifact->shouldReceive('getLastUpdateDate')->andReturn(123456789);
        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getId')->andReturn(101);
        $artifact->shouldReceive('getXRef')->andReturn("campaing #101");
        $artifact->shouldReceive('getTitle')->andReturn("Tuleap 12.1");
        $artifact->shouldReceive('userCanView')->andReturnTrue();

        $this->factory->shouldReceive('getInstanceFromRow')->andReturn($artifact);

        $this->glyph_finder->shouldReceive('get')->andReturn(\Mockery::mock(Glyph::class));

        $this->user_manager->shouldReceive('getUserById')->andReturn($user);
        $this->user_helper->shouldReceive('getLinkOnUser')->once();

        $this->collector->collect($collection);

        $this->assertCount(1, $collection->getLatestEntries());
    }
}
