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

declare(strict_types=1);

namespace Tuleap\CrossTracker\Permission;

use ForgeConfig;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\ForgeConfigSandbox;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Tracker\Permission\FieldPermissionType;
use Tuleap\Tracker\Permission\TrackersPermissionsRetriever;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\RetrieveUserPermissionOnFieldsStub;

final class CrossTrackerPermissionGateTest extends TestCase
{
    use ForgeConfigSandbox;

    protected function setUp(): void
    {
        ForgeConfig::setFeatureFlag(TrackersPermissionsRetriever::FEATURE_FLAG, 1);
    }

    public function testItDoesNotBlockLegitimateUser(): void
    {
        $this->expectNotToPerformAssertions();

        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userCanView')->willReturn(true);
        $column_field = ListFieldBuilder::aListField(101)->build();
        $search_field = ListFieldBuilder::aListField(102)->build();
        $report       = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field]);
        $report->method('getSearchFields')->willReturn([$search_field]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build(),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn([$column_field->getId(), $search_field->getId()], FieldPermissionType::PERMISSION_READ)
        );

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToAnyProjects(): void
    {
        $user     = UserTestBuilder::aUser()->build();
        $project1 = ProjectTestBuilder::aProject()->withId(101)->build();
        $project2 = ProjectTestBuilder::aProject()->withId(102)->build();

        $report = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project1, $project2]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build()
                ->withPrivateProjectForUser($project1, $user)
                ->withPrivateProjectForUser($project2, $user),
            RetrieveUserPermissionOnFieldsStub::build()
        );

        $this->expectException(CrossTrackerUnauthorizedProjectException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToAnyTrackers(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $tracker1 = $this->createMock(Tracker::class);
        $tracker1->method('userCanView')->willReturn(false);
        $tracker2 = $this->createMock(Tracker::class);
        $tracker2->method('userCanView')->willReturn(false);

        $project = ProjectTestBuilder::aProject()->build();

        $report = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker1, $tracker2]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build(),
            RetrieveUserPermissionOnFieldsStub::build()
        );

        $this->expectException(CrossTrackerUnauthorizedTrackerException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToAnyColumnFields(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userCanView')->willReturn(true);

        $column_field1 = ListFieldBuilder::aListField(101)->build();
        $column_field2 = ListFieldBuilder::aListField(102)->build();
        $report        = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field1, $column_field2]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build(),
            RetrieveUserPermissionOnFieldsStub::build()
        );

        $this->expectException(CrossTrackerUnauthorizedColumnFieldException::class);

        $permission_gate->check($user, $report);
    }

    public function testItBlocksUserThatCannotAccessToAnySearchFields(): void
    {
        $user    = UserTestBuilder::aUser()->build();
        $project = ProjectTestBuilder::aProject()->build();

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userCanView')->willReturn(true);

        $column_field  = ListFieldBuilder::aListField(101)->build();
        $search_field1 = ListFieldBuilder::aListField(102)->build();
        $search_field2 = ListFieldBuilder::aListField(103)->build();
        $report        = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project]);
        $report->method('getTrackers')->willReturn([$tracker]);
        $report->method('getColumnFields')->willReturn([$column_field]);
        $report->method('getSearchFields')->willReturn([$search_field1, $search_field2]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build(),
            RetrieveUserPermissionOnFieldsStub::build()->withPermissionOn([$column_field->getId()], FieldPermissionType::PERMISSION_READ)
        );

        $this->expectException(CrossTrackerUnauthorizedSearchFieldException::class);

        $permission_gate->check($user, $report);
    }

    public function testItDoesNotBlockUserWithPartialAccess(): void
    {
        $this->expectNotToPerformAssertions();

        $user = UserTestBuilder::aUser()->build();

        $tracker1 = $this->createMock(Tracker::class);
        $tracker1->method('userCanView')->willReturn(true);
        $tracker2 = $this->createMock(Tracker::class);
        $tracker2->method('userCanView')->willReturn(false);

        $project1 = ProjectTestBuilder::aProject()->withId(101)->build();
        $project2 = ProjectTestBuilder::aProject()->withId(102)->build();

        $report = $this->createMock(CrossTrackerReport::class);
        $report->method('getProjects')->willReturn([$project1, $project2]);
        $report->method('getTrackers')->willReturn([$tracker1, $tracker2]);
        $report->method('getColumnFields')->willReturn([]);
        $report->method('getSearchFields')->willReturn([]);

        $permission_gate = new CrossTrackerPermissionGate(
            CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser($project2, $user),
            RetrieveUserPermissionOnFieldsStub::build()
        );

        $permission_gate->check($user, $report);
    }
}
