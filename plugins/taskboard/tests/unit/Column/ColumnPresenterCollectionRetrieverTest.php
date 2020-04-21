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
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Planning;
use Planning_Milestone;
use Tracker;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;

class ColumnPresenterCollectionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ColumnPresenterCollectionRetriever */
    private $collection_retriever;
    /** @var Cardwall_OnTop_Config_ColumnFactory|M\LegacyMockInterface|M\MockInterface */
    private $column_factory;
    /** @var M\LegacyMockInterface|M\MockInterface|TrackerMappingPresenterBuilder */
    private $mappings_builder;

    protected function setUp(): void
    {
        $this->column_factory       = M::mock(Cardwall_OnTop_Config_ColumnFactory::class);
        $this->mappings_builder     = M::mock(TrackerMappingPresenterBuilder::class);
        $this->collection_retriever = new ColumnPresenterCollectionRetriever(
            $this->column_factory,
            $this->mappings_builder
        );
    }

    public function testEmptyCollection(): void
    {
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($milestone_tracker);
        $this->column_factory->shouldReceive('getDashboardColumns')
            ->with($milestone_tracker)
            ->once()
            ->andReturn([]);
        $user = M::mock(PFUser::class);

        $collection = $this->collection_retriever->getColumns($user, $milestone);

        $this->assertEmpty($collection);
    }

    public function testCollection(): void
    {
        $milestone_tracker = M::mock(Tracker::class);
        $milestone         = $this->mockMilestone($milestone_tracker);
        $milestone->shouldReceive('getArtifactId')
            ->atLeast()->once()
            ->andReturn(42);
        $todo_column       = new Cardwall_Column(2, 'To do', 'fiesta-red');
        $ongoing_column    = new Cardwall_Column(4, 'On going', '');
        $done_column       = new Cardwall_Column(6, 'Done', 'rgb(135,219,239)');
        $this->column_factory->shouldReceive('getDashboardColumns')
            ->with($milestone_tracker)
            ->once()
            ->andReturn([$todo_column, $ongoing_column, $done_column]);

        $user = M::mock(PFUser::class);
        $user->shouldReceive('getPreference')
             ->with('plugin_taskboard_collapse_column_42_2')
             ->once()
             ->andReturn(false);
        $user->shouldReceive('getPreference')
             ->with('plugin_taskboard_collapse_column_42_4')
             ->once()
             ->andReturn(false);
        $user->shouldReceive('getPreference')
             ->with('plugin_taskboard_collapse_column_42_6')
             ->once()
             ->andReturn('1');

        $this->mappings_builder->shouldReceive('buildMappings')->withArgs(
            function (Planning_Milestone $milestone, Cardwall_Column $column) {
                $column_id = $column->getId();
                return $column_id === 2 || $column_id === 4 || $column_id === 6;
            }
        )->andReturn([]);

        $collection = $this->collection_retriever->getColumns($user, $milestone);

        $this->assertCount(3, $collection);
        $this->assertFalse($collection[0]->is_collapsed);
        $this->assertFalse($collection[1]->is_collapsed);
        $this->assertTrue($collection[2]->is_collapsed);
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|Planning_Milestone
     */
    private function mockMilestone(Tracker $milestone_tracker)
    {
        $planning = M::mock(Planning::class);
        $planning->shouldReceive('getPlanningTracker')->once()->andReturn($milestone_tracker);
        $milestone = M::mock(Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->once()->andReturn($planning);
        return $milestone;
    }
}
