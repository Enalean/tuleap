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
use Tracker;

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

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->once()->andReturn(101);

        $retriever = new ColumnPresenterCollectionRetriever($dao);
        $collection = $retriever->getColumns($tracker);

        $this->assertEmpty($collection);
    }

    public function testCollection()
    {
        $dao = Mockery::mock(Cardwall_OnTop_ColumnDao::class);
        $dao->shouldReceive('searchColumnsByTrackerId')
            ->with(101)
            ->once()
            ->andReturn([
                ['id' => 2, 'label' => 'To do'],
                ['id' => 4, 'label' => 'Done']
            ]);

        $tracker = Mockery::mock(Tracker::class);
        $tracker->shouldReceive('getId')->once()->andReturn(101);

        $retriever = new ColumnPresenterCollectionRetriever($dao);
        $collection = $retriever->getColumns($tracker);

        $this->assertCount(2, $collection);
        $this->assertEquals('To do', $collection[0]->label);
        $this->assertEquals('Done', $collection[1]->label);
    }
}
