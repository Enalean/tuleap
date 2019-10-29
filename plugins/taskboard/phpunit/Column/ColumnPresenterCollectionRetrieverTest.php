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

use Cardwall_OnTop_ColumnDao;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenterBuilder;

class ColumnPresenterCollectionRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testEmptyCollection()
    {
        $dao = Mockery::mock(Cardwall_OnTop_ColumnDao::class);
        $dao->shouldReceive('searchColumnsByTrackerId')
            ->with(101)
            ->once()
            ->andReturn([]);

        $mappings_builder = Mockery::mock(TrackerMappingPresenterBuilder::class);

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')->once()->andReturn(101);

        $user = Mockery::mock(\PFUser::class);

        $milestone = Mockery::mock(\Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->once()->andReturn($planning);

        $retriever  = new ColumnPresenterCollectionRetriever($dao, $mappings_builder);
        $collection = $retriever->getColumns($user, $milestone);

        $this->assertEmpty($collection);
    }

    public function testCollection()
    {
        $dao = Mockery::mock(Cardwall_OnTop_ColumnDao::class);
        $dao->shouldReceive('searchColumnsByTrackerId')
            ->with(101)
            ->once()
            ->andReturn([
                ['id' => 2, 'label' => 'To do', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null, 'tlp_color_name' => 'fiesta_red'],
                ['id' => 4, 'label' => 'On going', 'bg_red' => null, 'bg_green' => null, 'bg_blue' => null, 'tlp_color_name' => ''],
                ['id' => 6, 'label' => 'Done', 'bg_red' => 135, 'bg_green' => 219, 'bg_blue' => 239, 'tlp_color_name' => '']
            ]);

        $planning = Mockery::mock(\Planning::class);
        $planning->shouldReceive('getPlanningTrackerId')->once()->andReturn(101);

        $user = Mockery::mock(\PFUser::class);
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

        $milestone = Mockery::mock(\Planning_Milestone::class);
        $milestone->shouldReceive('getPlanning')->once()->andReturn($planning);
        $milestone->shouldReceive('getArtifactId')->andReturn(42);

        $mappings_builder = Mockery::mock(TrackerMappingPresenterBuilder::class);
        $mappings_builder->shouldReceive('buildMappings')->withArgs(
            function (int $column_id, \Planning $planning) {
                return $column_id === 2 || $column_id === 4 || $column_id === 6;
            }
        )->andReturn([]);

        $retriever  = new ColumnPresenterCollectionRetriever($dao, $mappings_builder);
        $collection = $retriever->getColumns($user, $milestone);

        $this->assertCount(3, $collection);
        $this->assertEquals('To do', $collection[0]->label);
        $this->assertEquals('fiesta_red', $collection[0]->color);
        $this->assertFalse($collection[0]->is_collapsed);
        $this->assertEquals('On going', $collection[1]->label);
        $this->assertEquals('', $collection[1]->color);
        $this->assertFalse($collection[1]->is_collapsed);
        $this->assertEquals('Done', $collection[2]->label);
        $this->assertEquals('#87DBEF', $collection[2]->color);
        $this->assertTrue($collection[2]->is_collapsed);
    }
}
