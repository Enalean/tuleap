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
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use TrackerFactory;

final class MilestoneTrackerRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MilestoneTrackerRetriever $milestone_tracker_retriever;
    private Cardwall_OnTop_ColumnDao&MockObject $dao;
    private TrackerFactory&MockObject $tracker_factory;

    protected function setUp(): void
    {
        $this->dao                         = $this->createMock(Cardwall_OnTop_ColumnDao::class);
        $this->tracker_factory             = $this->createMock(TrackerFactory::class);
        $this->milestone_tracker_retriever = new MilestoneTrackerRetriever($this->dao, $this->tracker_factory);
    }

    public function testGetMilestoneTrackerOfColumnThrowsWhenGivenColumnCantBeFoundInDB(): void
    {
        $unknown_column = new Cardwall_Column(0, 'unknown', 'plum-crazy');
        $this->dao->method('searchByColumnId')
            ->with(0)
            ->willReturn(false);

        $this->expectException(InvalidColumnException::class);
        $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($unknown_column);
    }

    public function testGetMilestoneTrackerOfColumnReturnsTracker(): void
    {
        $done_column = new Cardwall_Column(16, 'Done', 'ocean-turquoise');
        $row         = ['tracker_id' => 87];
        $dar         = $this->createMock(\DataAccessResult::class);
        $dar->method('getRow')->willReturn($row);

        $this->dao->method('searchByColumnId')
            ->with(16)
            ->willReturn($dar);
        $milestone_tracker = $this->createMock(Tracker::class);
        $this->tracker_factory->method('getTrackerById')
            ->with(87)
            ->willReturn($milestone_tracker);

        $result = $this->milestone_tracker_retriever->getMilestoneTrackerOfColumn($done_column);
        self::assertSame($milestone_tracker, $result);
    }
}
