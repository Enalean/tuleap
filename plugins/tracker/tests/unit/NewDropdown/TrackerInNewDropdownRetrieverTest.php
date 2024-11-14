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

namespace Tuleap\Tracker\NewDropdown;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use TrackerFactory;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\PromotedTrackersRetriever;

class TrackerInNewDropdownRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PromotedTrackerDao
     */
    private $dao;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var PromotedTrackersRetriever
     */
    private $retriever;

    protected function setUp(): void
    {
        $this->dao             = Mockery::mock(PromotedTrackerDao::class);
        $this->tracker_factory = Mockery::mock(TrackerFactory::class);

        $this->retriever = new PromotedTrackersRetriever($this->dao, $this->tracker_factory);
    }

    public function testItReturnsTrackersUserCanSubmit(): void
    {
        $this->dao->shouldReceive('searchByProjectId')
            ->with(101)
            ->andReturn([
                ['id' => 123],
                ['id' => 124],
                ['id' => 125],
            ]);

        $tracker_123 = $this->aTracker(123, true);
        $tracker_124 = $this->aTracker(124, false);
        $tracker_125 = $this->aTracker(125, true);

        $project = Mockery::mock(\Project::class);
        $project->shouldReceive(['getID' => 101]);

        $trackers = $this->retriever->getTrackers(Mockery::mock(\PFUser::class), $project);
        $this->assertContains($tracker_123, $trackers);
        $this->assertNotContains($tracker_124, $trackers);
        $this->assertContains($tracker_125, $trackers);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|\Tracker
     */
    private function aTracker(int $id, bool $user_can_submit_artifact)
    {
        $tracker = Mockery::mock(\Tracker::class);

        $tracker->shouldReceive(['getId' => $id, 'userCanSubmitArtifact' => $user_can_submit_artifact]);

        $this->tracker_factory
            ->shouldReceive('getInstanceFromRow')
            ->with(['id' => $id])
            ->andReturn($tracker);

        return $tracker;
    }
}
