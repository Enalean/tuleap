<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\AgileDashboard;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class TaskboardUsageTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @testWith [false, true, true]
     *           ["cardwall", true, false]
     *           ["taskboard", false, true]
     *           ["anything", false, false]
     */
    public function testIsAllowed($board_type, bool $expected_is_cardwall_allowed, bool $expected_is_taskboard_allowed)
    {
        $project = \Mockery::mock(\Project::class);
        $project->shouldReceive('getId')->andReturn(101);

        $dao = \Mockery::mock(TaskboardUsageDao::class);
        $dao->shouldReceive('searchBoardTypeByProjectId')->andReturn($board_type);

        $usage = new TaskboardUsage($dao);

        $this->assertEquals($expected_is_cardwall_allowed, $usage->isCardwallAllowed($project));
        $this->assertEquals($expected_is_taskboard_allowed, $usage->isTaskboardAllowed($project));
    }
}
