<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Permission;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class CrossTrackerPermissionGateTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItDoesNotBlockLegitimateUser()
    {
        $user    = \Mockery::spy(\PFUser::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $project = \Mockery::spy(\Project::class);
        $tracker->shouldReceive('userCanView')->andReturn(true);
        $column_field = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $column_field->shouldReceive('userCanRead')->andReturn(true);
        $search_field = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $search_field->shouldReceive('userCanRead')->andReturn(true);
        $report  = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->shouldReceive('getProjects')->andReturn([$project]);
        $report->shouldReceive('getTrackers')->andReturn([$tracker]);
        $report->shouldReceive('getColumnFields')->andReturn([$column_field]);
        $report->shouldReceive('getSearchFields')->andReturn([$search_field]);

        $url_verification = \Mockery::spy(\URLVerification::class);
        $permission_gate  = new CrossTrackerPermissionGate($url_verification);
        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToProjects()
    {
        $user     = \Mockery::spy(\PFUser::class);
        $project1 = \Mockery::spy(\Project::class);
        $project2 = \Mockery::spy(\Project::class);
        $report   = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->shouldReceive('getProjects')->andReturn([$project1, $project2]);
        $url_verification = \Mockery::spy(\URLVerification::class);
        $url_verification->shouldReceive('userCanAccessProject')->with($user, $project1)->andReturn(true);
        $url_verification->shouldReceive('userCanAccessProject')->with($user, $project2)->andThrow(
            \Project_AccessPrivateException::class
        );

        $permission_gate = new CrossTrackerPermissionGate($url_verification);
        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedProjectException::class);
        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToTrackers()
    {
        $user     = \Mockery::spy(\PFUser::class);
        $tracker1 = \Mockery::spy(\Tracker::class);
        $tracker1->shouldReceive('userCanView')->andReturn(true);
        $tracker2 = \Mockery::spy(\Tracker::class);
        $tracker2->shouldReceive('userCanView')->andReturn(false);
        $project  = \Mockery::spy(\Project::class);
        $report   = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->shouldReceive('getProjects')->andReturn([$project]);
        $report->shouldReceive('getTrackers')->andReturn([$tracker1, $tracker2]);

        $url_verification = \Mockery::spy(\URLVerification::class);
        $permission_gate = new CrossTrackerPermissionGate($url_verification);
        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedTrackerException::class);
        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToColumnFields()
    {
        $user    = \Mockery::spy(\PFUser::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $project = \Mockery::spy(\Project::class);
        $tracker->shouldReceive('userCanView')->andReturn(true);
        $column_field1 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $column_field1->shouldReceive('userCanRead')->andReturn(true);
        $column_field2 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $column_field2->shouldReceive('userCanRead')->andReturn(false);
        $report  = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->shouldReceive('getProjects')->andReturn([$project]);
        $report->shouldReceive('getTrackers')->andReturn([$tracker]);
        $report->shouldReceive('getColumnFields')->andReturn([$column_field1, $column_field2]);

        $url_verification = \Mockery::spy(\URLVerification::class);
        $permission_gate  = new CrossTrackerPermissionGate($url_verification);
        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedColumnFieldException::class);
        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToSearchFields()
    {
        $user    = \Mockery::spy(\PFUser::class);
        $tracker = \Mockery::spy(\Tracker::class);
        $project = \Mockery::spy(\Project::class);
        $tracker->shouldReceive('userCanView')->andReturn(true);
        $column_field = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $column_field->shouldReceive('userCanRead')->andReturn(true);
        $search_field1 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $search_field1->shouldReceive('userCanRead')->andReturn(true);
        $search_field2 = \Mockery::spy(\Tracker_FormElement_Field_List::class);
        $search_field2->shouldReceive('userCanRead')->andReturn(false);
        $report  = \Mockery::spy(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->shouldReceive('getProjects')->andReturn([$project]);
        $report->shouldReceive('getTrackers')->andReturn([$tracker]);
        $report->shouldReceive('getColumnFields')->andReturn([$column_field]);
        $report->shouldReceive('getSearchFields')->andReturn([$search_field1, $search_field2]);

        $url_verification = \Mockery::spy(\URLVerification::class);
        $permission_gate  = new CrossTrackerPermissionGate($url_verification);
        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedSearchFieldException::class);
        $permission_gate->check($user, $report);
    }
}
