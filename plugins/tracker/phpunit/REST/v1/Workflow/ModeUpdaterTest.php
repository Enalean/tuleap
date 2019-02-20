<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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
 *
 */

namespace Tuleap\Tracker\REST\v1\Workflow;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Workflow;
use Workflow_Dao;

class ModeUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItSwitchToAdvancedMode()
    {
        $workflow_dao          = Mockery::mock(Workflow_Dao::class);
        $workflow_mode_updater = new ModeUpdater($workflow_dao);

        $tracker  = Mockery::mock(Tracker::class);
        $workflow = Mockery::mock(Workflow::class);

        $tracker->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('getId')->andReturn(25);

        $workflow_dao->shouldReceive('switchWorkflowToAdvancedMode')->with(25)->once();

        $workflow_mode_updater->switchWorkflowToAdvancedMode($tracker);
    }
}
