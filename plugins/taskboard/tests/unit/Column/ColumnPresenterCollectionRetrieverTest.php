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

namespace Tuleap\Taskboard\Column;

use Cardwall_Column;
use Cardwall_OnTop_Config_ColumnFactory;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Planning_Milestone;
use Tracker;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

class ColumnPresenterCollectionRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ColumnPresenterCollectionRetriever $collection_retriever;
    private Cardwall_OnTop_Config_ColumnFactory&MockObject $column_factory;
    private TrackerMappingPresenterBuilder&MockObject $mappings_builder;

    protected function setUp(): void
    {
        $this->column_factory       = $this->createMock(Cardwall_OnTop_Config_ColumnFactory::class);
        $this->mappings_builder     = $this->createMock(TrackerMappingPresenterBuilder::class);
        $this->collection_retriever = new ColumnPresenterCollectionRetriever(
            $this->column_factory,
            $this->mappings_builder
        );
    }

    public function testEmptyCollection(): void
    {
        $milestone_tracker = $this->createMock(Tracker::class);
        $milestone         = $this->mockMilestone($milestone_tracker);
        $this->column_factory->expects(self::once())
            ->method('getDashboardColumns')
            ->with($milestone_tracker)
            ->willReturn([]);

        $user = UserTestBuilder::aUser()->build();

        $collection = $this->collection_retriever->getColumns($user, $milestone);

        self::assertEmpty($collection);
    }

    public function testCollection(): void
    {
        $milestone_tracker = $this->createMock(Tracker::class);
        $milestone         = $this->mockMilestone($milestone_tracker);
        $milestone->expects(self::atLeast(1))
            ->method('getArtifactId')
            ->willReturn(42);
        $todo_column    = new Cardwall_Column(2, 'To do', 'fiesta-red');
        $ongoing_column = new Cardwall_Column(4, 'On going', '');
        $done_column    = new Cardwall_Column(6, 'Done', 'rgb(135,219,239)');
        $this->column_factory->expects(self::once())
            ->method('getDashboardColumns')
            ->with($milestone_tracker)
            ->willReturn([$todo_column, $ongoing_column, $done_column]);

        $user = $this->createMock(PFUser::class);
        $user->method('getPreference')
            ->willReturnMap([
                ['plugin_taskboard_collapse_column_42_2', false],
                ['plugin_taskboard_collapse_column_42_4', false],
                ['plugin_taskboard_collapse_column_42_6', '1'],
            ]);

        $this->mappings_builder->method('buildMappings')->with($milestone, self::callback(
            function (Cardwall_Column $column): bool {
                $column_id = $column->getId();
                return $column_id === 2 || $column_id === 4 || $column_id === 6;
            }
        ))->willReturn([]);

        $collection = $this->collection_retriever->getColumns($user, $milestone);

        self::assertCount(3, $collection);
        self::assertFalse($collection[0]->is_collapsed);
        self::assertFalse($collection[1]->is_collapsed);
        self::assertTrue($collection[2]->is_collapsed);
    }

    private function mockMilestone(Tracker $milestone_tracker): Planning_Milestone&MockObject
    {
        $planning = $this->createMock(Planning::class);
        $planning->expects(self::once())->method('getPlanningTracker')->willReturn($milestone_tracker);
        $milestone = $this->createMock(Planning_Milestone::class);
        $milestone->expects(self::once())->method('getPlanning')->willReturn($planning);
        return $milestone;
    }
}
