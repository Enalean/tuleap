<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Statistics;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class CollectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testSizeOccupiedByProjectAndCollectionTimeAreRetrieved(): void
    {
        $disk_usage_dao       = $this->createMock(\Statistics_DiskUsageDao::class);
        $statistics_retriever = $this->createStub(Retriever::class);

        $collector = new Collector($disk_usage_dao, $statistics_retriever);

        $project = $this->createStub(\Project::class);
        $project->method('getID')->willReturn(102);
        $params       = ['project' => $project, 'time_to_collect' => []];
        $current_time = new \DateTimeImmutable('17-12-2018');
        $statistics_retriever->method('getProjectDiskUsage')->willReturn(123456);

        $disk_usage_dao->expects(self::once())->method('addGroup');

        $collector->proceedToDiskUsageCollection($params, $current_time);

        $this->assertCount(1, $params['time_to_collect']);
    }
}
