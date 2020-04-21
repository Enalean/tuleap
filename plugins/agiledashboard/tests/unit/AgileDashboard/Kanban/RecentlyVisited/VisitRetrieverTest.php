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

use AgileDashboard_Kanban;
use AgileDashboard_KanbanCannotAccessException;
use AgileDashboard_KanbanFactory;
use AgileDashboard_KanbanNotFoundException;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tracker;
use TrackerFactory;
use Tuleap\Tracker\TrackerColor;
use Tuleap\User\History\HistoryEntryCollection;

class VisitRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var VisitRetriever
     */
    private $visit_retriever;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|RecentlyVisitedKanbanDao
     */
    private $dao;
    /**
     * @var AgileDashboard_KanbanFactory|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $kanban_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->user = Mockery::mock(PFUser::class);
        $this->user->shouldReceive('getId')->andReturn(101);

        $this->dao             = Mockery::mock(RecentlyVisitedKanbanDao::class);
        $this->kanban_factory  = Mockery::mock(AgileDashboard_KanbanFactory::class);
        $this->tracker_factory = Mockery::mock(TrackerFactory::class);

        $this->visit_retriever = new VisitRetriever($this->dao, $this->kanban_factory, $this->tracker_factory);
    }

    public function testItReturnsEmptyCollectionWhenThereIsntAnyEntriesInDb()
    {
        $this->dao
            ->shouldReceive('searchVisitByUserId')
            ->with(101, 10)
            ->once()
            ->andReturn([]);

        $collection = new HistoryEntryCollection($this->user);
        $this->visit_retriever->getVisitHistory($collection, 10);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresInvalidKanbanId()
    {
        $this->dao
            ->shouldReceive('searchVisitByUserId')
            ->with(101, 10)
            ->once()
            ->andReturn([['kanban_id' => 1]]);
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 1)
            ->once()
            ->andThrow(AgileDashboard_KanbanNotFoundException::class);

        $collection = new HistoryEntryCollection($this->user);
        $this->visit_retriever->getVisitHistory($collection, 10);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresKanbanThatCannotBeAccessed()
    {
        $this->dao
            ->shouldReceive('searchVisitByUserId')
            ->with(101, 10)
            ->once()
            ->andReturn([['kanban_id' => 1]]);
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 1)
            ->once()
            ->andThrow(AgileDashboard_KanbanCannotAccessException::class);

        $collection = new HistoryEntryCollection($this->user);
        $this->visit_retriever->getVisitHistory($collection, 10);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItIgnoresKanbanOfUnknownTracker()
    {
        $kanban = new AgileDashboard_Kanban(1, 12, 'Kanban Tasks');

        $this->dao
            ->shouldReceive('searchVisitByUserId')
            ->with(101, 10)
            ->once()
            ->andReturn([['kanban_id' => 1]]);
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 1)
            ->once()
            ->andReturn($kanban);
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(12)
            ->andReturnNull();

        $collection = new HistoryEntryCollection($this->user);
        $this->visit_retriever->getVisitHistory($collection, 10);

        $this->assertEmpty($collection->getEntries());
    }

    public function testItBuildEntries()
    {
        $kanban_1 = new AgileDashboard_Kanban(1, 12, 'Kanban Tasks');
        $kanban_2 = new AgileDashboard_Kanban(2, 24, 'Another Kanban');

        $project = Mockery::mock(Project::class);
        $project->shouldReceive('getId')
            ->andReturn(345);

        $tracker_12 = Mockery::mock(Tracker::class);
        $tracker_12->shouldReceive([
            'getName' => 'release',
            'getProject' => $project,
            'getColor' => TrackerColor::fromName('chrome-silver')
        ]);
        $tracker_24 = Mockery::mock(Tracker::class);
        $tracker_24->shouldReceive([
            'getName' => 'sprint',
            'getProject' => $project,
            'getColor' => TrackerColor::fromName('red-wine')
        ]);

        $this->dao
            ->shouldReceive('searchVisitByUserId')
            ->with(101, 10)
            ->once()
            ->andReturn([
                ['kanban_id' => 1, 'created_on' => 1234],
                ['kanban_id' => 2, 'created_on' => 6789],
            ]);
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 1)
            ->once()
            ->andReturn($kanban_1);
        $this->kanban_factory
            ->shouldReceive('getKanban')
            ->with($this->user, 2)
            ->once()
            ->andReturn($kanban_2);
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(12)
            ->andReturn($tracker_12);
        $this->tracker_factory
            ->shouldReceive('getTrackerById')
            ->with(24)
            ->andReturn($tracker_24);

        $collection = new HistoryEntryCollection($this->user);
        $this->visit_retriever->getVisitHistory($collection, 10);

        $this->assertCount(2, $collection->getEntries());
    }
}
