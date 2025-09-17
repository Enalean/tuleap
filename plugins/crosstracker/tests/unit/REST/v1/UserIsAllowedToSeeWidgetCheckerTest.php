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
use Tuleap\CrossTracker\Tests\Stub\Widget\RetrieveCrossTrackerWidgetStub;
use Tuleap\CrossTracker\Widget\ProjectCrossTrackerWidget;
use Tuleap\CrossTracker\Widget\UserCrossTrackerWidget;
use Tuleap\Dashboard\Project\ProjectDashboardController;
use Tuleap\Dashboard\User\UserDashboardController;
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

    private const int USER_ID = 102;

    private PFUser $user;
    private ProjectByIDFactoryStub $project_manager;
    private CheckUserCanAccessProject&CheckUserCanAccessProjectAndIsAdmin $url_verification;

    #[\Override]
    protected function setUp(): void
    {
        $this->user             = UserTestBuilder::buildWithId(self::USER_ID);
        $this->project_manager  = ProjectByIDFactoryStub::buildWithoutProject();
        $this->url_verification = CheckUserCanAccessProjectStub::build();

        $GLOBALS['Language']->method('getText')->willReturnCallback(static fn(string $msg) => $msg);
    }

    private function checkUserIsAllowedToSeeWidget(UserCrossTrackerWidget|ProjectCrossTrackerWidget $widget): void
    {
        $user_is_allowed_to_see_widget_checker = new UserIsAllowedToSeeWidgetChecker(
            $this->project_manager,
            $this->url_verification,
            RetrieveCrossTrackerWidgetStub::withWidget($widget),
        );

        $user_is_allowed_to_see_widget_checker->checkUserIsAllowedToSeeWidget($this->user, 1);
    }

    private function checkUserIsAllowedToUpdateWidget(UserCrossTrackerWidget|ProjectCrossTrackerWidget $widget): void
    {
        $user_is_allowed_to_see_widget_checker = new UserIsAllowedToSeeWidgetChecker(
            $this->project_manager,
            $this->url_verification,
            RetrieveCrossTrackerWidgetStub::withWidget($widget)
        );

        $user_is_allowed_to_see_widget_checker->checkUserIsAllowedToUpdateWidget($this->user, 1);
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeAnotherUserWidget(): void
    {
        $this->user = UserTestBuilder::buildWithId(104);
        $widget     = UserCrossTrackerWidget::build(1, UserDashboardController::DASHBOARD_TYPE, 105);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->checkUserIsAllowedToSeeWidget($widget);
    }

    public function testTheUserCanViewTheUserWidget(): void
    {
        $widget = UserCrossTrackerWidget::build(1, UserDashboardController::DASHBOARD_TYPE, (int) $this->user->getId());

        $this->checkUserIsAllowedToSeeWidget($widget);

        self::expectNotToPerformAssertions();
    }

    public function testItThrowsExceptionWhenTheCurrentUserWantToSeeFromPrivateProjectWithoutAccess(): void
    {
        $project                = ProjectTestBuilder::aProject()->withId(105)->build();
        $widget                 = ProjectCrossTrackerWidget::build(1, ProjectDashboardController::DASHBOARD_TYPE, (int) $project->getId());
        $this->project_manager  = ProjectByIDFactoryStub::buildWith($project);
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withPrivateProjectForUser(
            ProjectTestBuilder::aProject()->withId(105)->build(),
            $this->user,
        );

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->checkUserIsAllowedToSeeWidget($widget);
    }

    public function testTheUserCanViewTheProjectWidget(): void
    {
        $project               = ProjectTestBuilder::aProject()->withId(105)->build();
        $widget                = ProjectCrossTrackerWidget::build(1, ProjectDashboardController::DASHBOARD_TYPE, (int) $project->getId());
        $this->project_manager = ProjectByIDFactoryStub::buildWith($project);

        $this->checkUserIsAllowedToSeeWidget($widget);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCanUpdateTheProjectWidget(): void
    {
        $project                = ProjectTestBuilder::aProject()->withId(105)->build();
        $widget                 = ProjectCrossTrackerWidget::build(1, ProjectDashboardController::DASHBOARD_TYPE, (int) $project->getId());
        $this->user             = UserTestBuilder::anActiveUser()->withAdministratorOf($project)->build();
        $this->project_manager  = ProjectByIDFactoryStub::buildWith($project);
        $this->url_verification = CheckUserCanAccessProjectStub::build()->withUserAdminOf($this->user, $project);

        $this->checkUserIsAllowedToUpdateWidget($widget);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCannotUpdateTheProjectWidget(): void
    {
        $project                = ProjectTestBuilder::aProject()->withId(105)->build();
        $widget                 = ProjectCrossTrackerWidget::build(1, ProjectDashboardController::DASHBOARD_TYPE, (int) $project->getId());
        $this->project_manager  = ProjectByIDFactoryStub::buildWith($project);
        $this->url_verification = CheckUserCanAccessProjectStub::build();

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->checkUserIsAllowedToUpdateWidget($widget);
    }

    public function testTheUserCanUpdateTheUserWidget(): void
    {
        $widget = UserCrossTrackerWidget::build(1, UserDashboardController::DASHBOARD_TYPE, (int) $this->user->getId());

        $this->checkUserIsAllowedToUpdateWidget($widget);

        self::expectNotToPerformAssertions();
    }

    public function testTheUserCannotUpdateTheUserWidget(): void
    {
        $widget     = UserCrossTrackerWidget::build(1, UserDashboardController::DASHBOARD_TYPE, (int) $this->user->getId());
        $this->user = UserTestBuilder::buildWithId(215);

        $this->expectException(RestException::class);
        $this->expectExceptionCode(404);

        $this->checkUserIsAllowedToUpdateWidget($widget);
    }
}
