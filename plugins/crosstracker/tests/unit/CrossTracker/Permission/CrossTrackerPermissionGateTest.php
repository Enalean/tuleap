<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class CrossTrackerPermissionGateTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItDoesNotBlockLegitimateUser(): void
    {
        $this->expectNotToPerformAssertions();

        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userCanView')->willReturn(true);
        $column_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $column_field->method('userCanRead')->willReturn(true);
        $search_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $search_field->method('userCanRead')->willReturn(true);
        $report = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field]);
        $report->method('getSearchFields')->willReturn([$search_field]);

        $url_verification = $this->createMock(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willReturn(true);

        $permission_gate = new CrossTrackerPermissionGate($url_verification);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToProjects(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $project1 = ProjectTestBuilder::aProject()->build();
        $project2 = ProjectTestBuilder::aProject()->build();

        $report = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project1, $project2]);

        $url_verification = $this->createMock(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willReturnCallback(
            function (\PFUser $user_param, \Project $project_param) use ($user, $project1, $project2): bool {
                if ($user_param === $user && $project_param === $project1) {
                    return true;
                } elseif ($user_param === $user && $project_param === $project2) {
                    throw new \Project_AccessPrivateException();
                }

                return false;
            }
        );

        $permission_gate = new CrossTrackerPermissionGate($url_verification);

        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedProjectException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToTrackers(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $tracker1 = $this->createMock(\Tracker::class);
        $tracker1->method('userCanView')->willReturn(true);
        $tracker2 = $this->createMock(\Tracker::class);
        $tracker2->method('userCanView')->willReturn(false);

        $project = ProjectTestBuilder::aProject()->build();

        $report = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker1, $tracker2]);

        $url_verification = $this->createMock(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willReturn(true);

        $permission_gate = new CrossTrackerPermissionGate($url_verification);

        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedTrackerException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToColumnFields(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userCanView')->willReturn(true);

        $column_field1 = $this->createMock(\Tracker_FormElement_Field_List::class);
        $column_field1->method('userCanRead')->willReturn(true);
        $column_field2 = $this->createMock(\Tracker_FormElement_Field_List::class);
        $column_field2->method('userCanRead')->willReturn(false);
        $report = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field1, $column_field2]);

        $url_verification = $this->createMock(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willReturn(true);

        $permission_gate = new CrossTrackerPermissionGate($url_verification);

        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedColumnFieldException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToSearchFields(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('userCanView')->willReturn(true);

        $column_field = $this->createMock(\Tracker_FormElement_Field_List::class);
        $column_field->method('userCanRead')->willReturn(true);
        $search_field1 = $this->createMock(\Tracker_FormElement_Field_List::class);
        $search_field1->method('userCanRead')->willReturn(true);
        $search_field2 = $this->createMock(\Tracker_FormElement_Field_List::class);
        $search_field2->method('userCanRead')->willReturn(false);
        $report = $this->createMock(\Tuleap\CrossTracker\CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field]);
        $report->method('getSearchFields')->willReturn([$search_field1, $search_field2]);

        $url_verification = $this->createMock(\URLVerification::class);
        $url_verification->method('userCanAccessProject')->willReturn(true);

        $permission_gate = new CrossTrackerPermissionGate($url_verification);

        $this->expectException(\Tuleap\CrossTracker\Permission\CrossTrackerUnauthorizedSearchFieldException::class);

        $permission_gate->check($user, $report);
    }
}
