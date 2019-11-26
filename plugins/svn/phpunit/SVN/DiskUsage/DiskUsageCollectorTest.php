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

namespace Tuleap\SVN\DiskUsage;

use DateTimeImmutable;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Statistics_DiskUsageDao;
use SvnPlugin;

class DiskUsageCollectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItCollectsTheDiskUsage()
    {
        $retriever = Mockery::mock(DiskUsageRetriever::class);
        $dao       = Mockery::mock(Statistics_DiskUsageDao::class);

        $collector = new DiskUsageCollector($retriever, $dao);

        $project      = Mockery::mock(Project::class);
        $collect_date = new DateTimeImmutable();

        $project->shouldReceive('getID')->once()->andReturn(102);

        $retriever->shouldReceive('getDiskUsageForProject')
            ->once()
            ->with($project)
            ->andReturn(156);

        $dao->shouldReceive('addGroup')
            ->once()
            ->with(
                102,
                SvnPlugin::SERVICE_SHORTNAME,
                156,
                $collect_date->getTimestamp()
            );

        $collector->collectDiskUsageForProject($project, $collect_date);
    }
}
