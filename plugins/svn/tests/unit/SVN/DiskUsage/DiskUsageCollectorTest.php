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
use Statistics_DiskUsageDao;
use SvnPlugin;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class DiskUsageCollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCollectsTheDiskUsage(): void
    {
        $retriever = $this->createMock(DiskUsageRetriever::class);
        $dao       = $this->createMock(Statistics_DiskUsageDao::class);

        $collector = new DiskUsageCollector($retriever, $dao);

        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $collect_date = new DateTimeImmutable();

        $retriever->expects(self::once())
            ->method('getDiskUsageForProject')
            ->with($project)
            ->willReturn(156);

        $dao->expects(self::once())
            ->method('addGroup')
            ->with(
                102,
                SvnPlugin::SERVICE_SHORTNAME,
                156,
                $collect_date->getTimestamp()
            );
        $retriever->method('hasCoreStatistics')->willReturn(false);

        $collector->collectDiskUsageForProject($project, $collect_date);
    }

    public function testItOverrideSvnCoreComputedStat(): void
    {
        $retriever = $this->createMock(DiskUsageRetriever::class);
        $dao       = $this->createMock(Statistics_DiskUsageDao::class);

        $collector = new DiskUsageCollector($retriever, $dao);

        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $collect_date = new DateTimeImmutable();

        $retriever->expects(self::once())
            ->method('getDiskUsageForProject')
            ->with($project)
            ->willReturn(156);

        $dao->expects(self::once())
            ->method('addGroup')
            ->with(
                102,
                SvnPlugin::SERVICE_SHORTNAME,
                156,
                $collect_date->getTimestamp()
            );

        $dao->expects(self::once())->method('updateGroup')->with($project, $collect_date, 'svn', '0');

        $retriever->method('hasCoreStatistics')->willReturn(true);

        $collector->collectDiskUsageForProject($project, $collect_date);
    }
}
