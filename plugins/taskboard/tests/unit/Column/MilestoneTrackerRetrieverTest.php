<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use Cardwall_OnTop_ColumnDao;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use TrackerFactory;

final class MilestoneTrackerRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var MilestoneTrackerRetriever */
    private $milestone_tracker_retriever;
    /** @var Cardwall_OnTop_ColumnDao|M\LegacyMockInterface|M\MockInterface */
    private $dao;
    /** @var M\LegacyMockInterface|M\MockInterface|TrackerFactory */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->dao                         = M::mock(Cardwall_OnTop_ColumnDao::class);
        $this->tracker_factory             = M::mock(TrackerFactory::class);
        $this->milestone_tracker_retriever = new MilestoneTrackerRetriever($this->dao, $this->tracker_factory);
    }

    public function testGetMilestoneTrackerOfColumnThrowsWhenGivenColumnCantBeFoundInDB(): void
    {
        $unknown_column = new Cardwall_Column(0, 'unknown', 'plum-crazy');
        $this->dao->shouldReceive('searchByColumnId')
            ->with(0)
            ->andReturnFalse();

        $this->expectException(InvalidColumnException::class);
        $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($unknown_column);
    }

    public function testGetMilestoneTrackerOfColumnReturnsTracker(): void
    {
        $done_column = new Cardwall_Column(16, 'Done', 'ocean-turquoise');
        $row         = ['tracker_id' => 87];
        $dar         = M::mock(\DataAccessResult::class)
            ->shouldReceive(['getRow' => $row])
            ->getMock();
        $this->dao->shouldReceive('searchByColumnId')
            ->with(16)
            ->andReturn($dar);
        $milestone_tracker = M::mock(Tracker::class);
        $this->tracker_factory->shouldReceive('getTrackerById')
            ->with(87)
            ->andReturn($milestone_tracker);

        $result = $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($done_column);
        $this->assertSame($milestone_tracker, $result);
    }
}
