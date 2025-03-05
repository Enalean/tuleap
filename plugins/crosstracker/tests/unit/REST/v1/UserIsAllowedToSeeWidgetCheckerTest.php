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
use Tuleap\CrossTracker\Widget\SearchCrossTrackerWidget;
use Tuleap\GlobalLanguageMock;
use Tuleap\include\CheckUserCanAccessProject;
use Tuleap\include\CheckUserCanAccessProjectAndIsAdmin;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\include\CheckUserCanAccessProjectStub;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserIsAllowedToSeeWidgetCheckerTest extends TestCase
{
    use GlobalLanguageMock;

    private const USER_ID = 102;

    private PFUser $user;
    private ProjectByIDFactoryStub $project_manager;
    private CheckUserCanAccessProject&CheckUserCanAccessProjectAndIsAdmin $url_verification;

    protected function setUp(): void
    {
        $this->user             = UserTestBuilder::buildWithId(self::USER_ID);
        $this->project_manager  = ProjectByIDFactoryStub::buildWithoutProject();
        $this->url_verification = CheckUserCanAccessProjectStub::build();

        $GLOBALS['Language']->method('getText')->willReturnCallback(static fn(string $msg) => $msg);
    }

    private function checkUserIsAllowedToSeeWidget(SearchCrossTrackerWidget $cross_tracker_dao): void
    {
        $user_is_allowed_to_see_report_checker = new UserIsAllowedToSeeWidgetChecker(
            $cross_tracker_dao,
            $this->project_manager,
            $this->url_verification,
        );

        $user_is_allowed_to_see_report_checker->checkUserIsAllowedToSeeWidget($this->user, 1);
    }

    private function checkUserIsAllowedToUpdateWidget(SearchCrossTrackerWidget $cross_tracker_dao): void
    {
        $user_is_allowed_to_see_report_checker = new UserIsAllowedToSeeWidgetChecker(
            $cross_tracker_dao,
            $this->project_manager,
            $this->url_verification,
        );

        $user_is_allowed_to_see_report_checker->checkUserIsAllowedToUpdateWidget($this->user, 1);
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeAnotherUserWidget(): void
    {
        $this->user        = UserTestBuilder::buildWithId(104);
        $cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'user',
                'user_id'        => 105,
            ]
        );

        self::expectException(RestException::class);
        self::expectExceptionCode(404);

        $this->checkUserIsAllowedToSeeWidget($cross_tracker_dao);
    }

    public function testTheUserCanViewTheUserWidget(): void
    {
        $this->user        = UserTestBuilder::buildWithId(104);
        $cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'user',
                'user_id'        => 104,
            ]
        );

        $this->checkUserIsAllowedToSeeWidget($cross_tracker_dao);

        self::expectNotToPerformAssertions();
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeFromPrivateProjectWithoutAccess(): void
    {
        $cross_tracker_dao      = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $this->project_manager  = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(105)->build());
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser(
            ProjectTestBuilder::aProject()->withId(105)->build(),
            $this->user,
        );

        self::expectException(RestException::class);
        self::expectExceptionCode(404);

        $this->checkUserIsAllowedToSeeWidget($cross_tracker_dao);
    }

    public function testTheUserCanViewTheProjectWidget(): void
    {
        $cross_tracker_dao     = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $this->project_manager = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(105)->build());

        $this->checkUserIsAllowedToSeeWidget($cross_tracker_dao);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCanUpdateTheProjectWidget(): void
    {
        $cross_tracker_dao      = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $project                = ProjectTestBuilder::aProject()->withId(105)->build();
        $this->user             = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();
        $this->project_manager  = ProjectByIDFactoryStub::buildWith($project);
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withUserAdminOf($this->user, $project);

        $this->checkUserIsAllowedToUpdateWidget($cross_tracker_dao);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCannotUpdateTheProjectWidget(): void
    {
        $cross_tracker_dao      = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'project',
                'project_id'     => 105,
            ]
        );
        $this->project_manager  = ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(105)->build());
        $this->url_verification = CheckUserCanAccessProjectStub::build();

        self::expectException(RestException::class);
        self::expectExceptionCode(404);

        $this->checkUserIsAllowedToUpdateWidget($cross_tracker_dao);
    }

    public function testTheUserCanUpdateTheUserWidget(): void
    {
        $cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'user',
                'user_id'        => 105,
            ]
        );
        $this->user        = UserTestBuilder::buildWithId(105);

        $this->checkUserIsAllowedToUpdateWidget($cross_tracker_dao);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCannotUpdateTheUserWidget(): void
    {
        $cross_tracker_dao = SearchCrossTrackerWidgetStub::withExistingWidget(
            [
                'dashboard_type' => 'user',
                'user_id'        => 105,
            ]
        );
        $this->user        = UserTestBuilder::buildWithId(215);

        self::expectException(RestException::class);
        self::expectExceptionCode(404);

        $this->checkUserIsAllowedToUpdateWidget($cross_tracker_dao);
    }
}
