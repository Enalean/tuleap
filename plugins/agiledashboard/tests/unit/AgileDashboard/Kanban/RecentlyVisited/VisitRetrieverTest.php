<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Kanban\RecentlyVisited;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\TrackerColor;
use Tuleap\User\History\HistoryEntryCollection;

final class VisitRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_ID                       = 101;
    private const MAX_LENGTH                    = 10;
    private const FIRST_KANBAN_ID               = 1;
    private const FIRST_TRACKER_ID              = 12;
    private const FIRST_KANBAN_VISIT_TIMESTAMP  = 1491246376;
    private const SECOND_KANBAN_ID              = 2;
    private const SECOND_TRACKER_ID             = 24;
    private const SECOND_KANBAN_VISIT_TIMESTAMP = 1522959274;
    /**
     * @var RecentlyVisitedKanbanDao&MockObject
     */
    private $dao;
    /**
     * @var \AgileDashboard_KanbanFactory&MockObject
     */
    private $kanban_factory;
    /**
     * @var \TrackerFactory&MockObject
     */
    private $tracker_factory;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->user = UserTestBuilder::buildWithId(self::USER_ID);

        $this->dao             = $this->createMock(RecentlyVisitedKanbanDao::class);
        $this->kanban_factory  = $this->createMock(\AgileDashboard_KanbanFactory::class);
        $this->tracker_factory = $this->createMock(\TrackerFactory::class);
    }

    private function getVisitHistory(HistoryEntryCollection $collection): void
    {
        $visit_retriever = new VisitRetriever($this->dao, $this->kanban_factory, $this->tracker_factory);

        $visit_retriever->getVisitHistory($collection, self::MAX_LENGTH);
    }

    public function testItReturnsEmptyCollectionWhenThereIsntAnyEntriesInDb(): void
    {
        $this->dao->method('searchVisitByUserId')
            ->with(self::USER_ID, self::MAX_LENGTH)
            ->willReturn([]);

        $collection = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresInvalidKanbanId(): void
    {
        $this->dao->method('searchVisitByUserId')
            ->with(self::USER_ID, self::MAX_LENGTH)
            ->willReturn([['kanban_id' => self::FIRST_KANBAN_ID, 'created_on' => self::FIRST_KANBAN_VISIT_TIMESTAMP]]);
        $this->kanban_factory->method('getKanban')
            ->with($this->user, self::FIRST_KANBAN_ID)
            ->willThrowException(new \AgileDashboard_KanbanNotFoundException());

        $collection = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresKanbanThatCannotBeAccessed(): void
    {
        $this->dao->method('searchVisitByUserId')
            ->with(self::USER_ID, self::MAX_LENGTH)
            ->willReturn([['kanban_id' => self::FIRST_KANBAN_ID, 'created_on' => self::FIRST_KANBAN_VISIT_TIMESTAMP]]);
        $this->kanban_factory->method('getKanban')
            ->with($this->user, self::FIRST_KANBAN_ID)
            ->willThrowException(new \AgileDashboard_KanbanCannotAccessException());

        $collection = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresKanbanOfUnknownTracker(): void
    {
        $kanban = new \AgileDashboard_Kanban(self::FIRST_KANBAN_ID, self::FIRST_TRACKER_ID, 'Kanban Tasks');

        $this->dao->method('searchVisitByUserId')
            ->with(self::USER_ID, self::MAX_LENGTH)
            ->willReturn([['kanban_id' => self::FIRST_KANBAN_ID, 'created_on' => self::FIRST_KANBAN_VISIT_TIMESTAMP]]);
        $this->kanban_factory->method('getKanban')
            ->with($this->user, self::FIRST_KANBAN_ID)
            ->willReturn($kanban);
        $this->tracker_factory->method('getTrackerById')
            ->with(self::FIRST_TRACKER_ID)
            ->willReturn(null);

        $collection = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItBuildEntries(): void
    {
        $kanban_1 = new \AgileDashboard_Kanban(self::FIRST_KANBAN_ID, self::FIRST_TRACKER_ID, 'Kanban Tasks');
        $kanban_2 = new \AgileDashboard_Kanban(self::SECOND_KANBAN_ID, self::SECOND_TRACKER_ID, 'Another Kanban');

        $project = ProjectTestBuilder::aProject()->withId(345)->build();

        $tracker_12 = TrackerTestBuilder::aTracker()->withName('release')
            ->withProject($project)
            ->withColor(TrackerColor::fromName('chrome-silver'))
            ->build();
        $tracker_24 = TrackerTestBuilder::aTracker()->withName('sprint')
            ->withProject($project)
            ->withColor(TrackerColor::fromName('red-wine'))
            ->build();

        $this->dao->method('searchVisitByUserId')
            ->with(self::USER_ID, self::MAX_LENGTH)
            ->willReturn([
                ['kanban_id' => self::FIRST_KANBAN_ID, 'created_on' => self::FIRST_KANBAN_VISIT_TIMESTAMP],
                ['kanban_id' => self::SECOND_KANBAN_ID, 'created_on' => self::SECOND_KANBAN_VISIT_TIMESTAMP],
            ]);
        $this->kanban_factory->method('getKanban')
            ->willReturnOnConsecutiveCalls($kanban_1, $kanban_2);
        $this->tracker_factory->method('getTrackerById')
            ->willReturnOnConsecutiveCalls($tracker_12, $tracker_24);

        $collection = new HistoryEntryCollection($this->user);
        $this->getVisitHistory($collection);

        $this->assertCount(2, $collection->getEntries());
    }
}
