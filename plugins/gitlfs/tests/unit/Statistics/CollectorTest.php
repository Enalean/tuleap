<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

use Mockery;

class CollectorTest extends \PHPUnit\Framework\TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testSizeOccupiedByProjectAndCollectionTimeAreRetrieved()
    {
        $disk_usage_dao       = Mockery::mock(\Statistics_DiskUsageDao::class);
        $statistics_retriever = Mockery::mock(Retriever::class);

        $collector = new Collector($disk_usage_dao, $statistics_retriever);

        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getID')->andReturns(102);
        $params       = ['project' => $project, 'time_to_collect' => []];
        $current_time = new \DateTimeImmutable('17-12-2018');
        $statistics_retriever->shouldReceive('getProjectDiskUsage')->andReturns(123456);

        $disk_usage_dao->shouldReceive('addGroup')->once();

        $collector->proceedToDiskUsageCollection($params, $current_time);

        $this->assertCount(1, $params['time_to_collect']);
    }
}
