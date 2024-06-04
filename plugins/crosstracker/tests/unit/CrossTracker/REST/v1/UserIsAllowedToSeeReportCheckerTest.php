<?php
/**
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Tuleap\CrossTracker\REST\v1;

use Luracast\Restler\RestException;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tracker;
use Tuleap\CrossTracker\CrossTrackerReport;
use Tuleap\CrossTracker\Permission\CrossTrackerPermissionGate;
use Tuleap\CrossTracker\SearchCrossTrackerWidgetStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use Tuleap\Tracker\Test\Builders\Fields\TextFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\RetrieveUserPermissionOnFieldsStub;
use Tuleap\Tracker\Test\Stub\Tracker\Permission\RetrieveUserPermissionOnTrackersStub;

final class UserIsAllowedToSeeReportCheckerTest extends TestCase
{
    private const USER_ID = 102;

    private SearchCrossTrackerWidgetStub $cross_tracker_dao;
    private PFUser $user;
    private ProjectByIDFactoryStub $project_manager;
    private CheckUserCanAccessProjectStub $url_verification;

    protected function setUp(): void
    {
        $this->cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'user',
                'user_id'        => self::USER_ID,
            ]
        );


        $this->user             = UserTestBuilder::buildWithId(self::USER_ID);
        $this->project_manager  = ProjectByIDFactoryStub::buildWithoutProject();
        $this->url_verification =  CheckUserCanAccessProjectStub::build();
    }

    private function checkUserIsAllowedToSeeReport(Tracker|MockObject $tracker): void
    {
        $user_is_allowed_to_see_report_checker = new UserIsAllowedToSeeReportChecker(
            $this->cross_tracker_dao,
            $this->project_manager,
            $this->url_verification,
            new CrossTrackerPermissionGate(
                $this->url_verification,
                RetrieveUserPermissionOnFieldsStub::build(),
                RetrieveUserPermissionOnTrackersStub::build()
            )
        );

        $user_is_allowed_to_see_report_checker->checkUserIsAllowedToSeeReport(
            $this->user,
            new CrossTrackerReport(
                1,
                '',
                [$tracker],
            ),
        );
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeAnotherUserWidget(): void
    {
        $this->user = UserTestBuilder::buildWithId(104);

        self::expectException(RestException::class);
        self::expectExceptionCode(403);

        $this->checkUserIsAllowedToSeeReport(TrackerTestBuilder::aTracker()->withId(1)->build());
    }

    public function testItThrowsExceptionWhenTheCrossTrackerCheckFails(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(105)->build();

        $this->url_verification = CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser(
            $project,
            $this->user,
        );

        self::expectException(RestException::class);
        self::expectExceptionCode(403);

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('userCanView')->willReturn(false);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(115)->build());

        $this->checkUserIsAllowedToSeeReport($tracker);
    }

    public function testTheUserCanViewTheProjectReport(): void
    {
        $this->cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'        => 105,
            ]
        );

        $this->project_manager = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(201)->build());

        $project                = ProjectTestBuilder::aProject()->withId(105)->build();
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser(
            $project,
            $this->user,
        );

        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(15);
        $tracker->method('userCanView')->willReturn(true);
        $tracker->method('getProject')->willReturn(ProjectTestBuilder::aProject()->withId(115)->build());
        $tracker->method('getTitleField')->willReturn(
            TextFieldBuilder::aTextField(10)->inTracker($tracker)->withReadPermission($this->user, true)->build()
        );
        $tracker->method('getStatusField')->willReturn(
            ListFieldBuilder::aListField(11)->inTracker($tracker)->withReadPermission($this->user, true)->build()
        );
        $tracker->method('getContributorField')->willReturn(
            ListFieldBuilder::aListField(12)->inTracker($tracker)->withReadPermission($this->user, true)->build()
        );

        $this->checkUserIsAllowedToSeeReport(
            $tracker
        );

        self::expectNotToPerformAssertions();
    }
}
