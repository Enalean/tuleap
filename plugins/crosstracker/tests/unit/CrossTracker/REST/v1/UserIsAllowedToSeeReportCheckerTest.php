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
use Tuleap\CrossTracker\SearchCrossTrackerWidgetStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

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
        $this->url_verification = CheckUserCanAccessProjectStub::build();
    }

    private function checkUserIsAllowedToSeeReport(): void
    {
        $user_is_allowed_to_see_report_checker = new UserIsAllowedToSeeWidgetChecker(
            $this->cross_tracker_dao,
            $this->project_manager,
            $this->url_verification
        );

        $user_is_allowed_to_see_report_checker->checkUserIsAllowedToSeeWidget($this->user, 1);
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeAnotherUserWidget(): void
    {
        $this->user = UserTestBuilder::buildWithId(104);

        self::expectException(RestException::class);
        self::expectExceptionCode(403);

        $this->checkUserIsAllowedToSeeReport();
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeFromPrivateProjectWithoutAccess(): void
    {
        $this->cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $this->project_manager   = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(105)->build());
        $this->url_verification  = CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser(
            ProjectTestBuilder::aProject()->withId(105)->build(),
            $this->user,
        );

        self::expectException(RestException::class);
        self::expectExceptionCode(403);

        $this->checkUserIsAllowedToSeeReport();
    }

    public function testTheUserCanViewTheProjectReport(): void
    {
        $this->cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $this->project_manager   = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(105)->build());

        $this->checkUserIsAllowedToSeeReport();

        self::expectNotToPerformAssertions();
    }
}
