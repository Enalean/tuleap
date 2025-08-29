<?php
/**
 * Copyright (c) Enalean, 2014 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use Override;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Milestone\Sidebar\DuplicateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Milestone\Sidebar\UpdateMilestonesInSidebarConfig;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const int PROJECT_ID = 123;
    private \Codendi_Request&Stub $request;
    private ConfigurationDao&MockObject $configuration_dao;
    private \EventManager&Stub $event_manager;
    private CountElementsModeChecker&Stub $count_element_mode_checker;
    private \PFUser $user;

    #[Override]
    protected function setUp(): void
    {
        $this->request                    = $this->createStub(\Codendi_Request::class);
        $this->configuration_dao          = $this->createMock(ConfigurationDao::class);
        $this->event_manager              = $this->createStub(\EventManager::class);
        $this->count_element_mode_checker = $this->createStub(CountElementsModeChecker::class);

        $this->count_element_mode_checker->method('burnupMustUseCountElementsMode')->willReturn(false);
        $this->event_manager->method('dispatch')->willReturnArgument(0);

        $this->user = UserTestBuilder::buildWithDefaults();
    }

    private function update(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $this->request->method('getCurrentUser')->willReturn($this->user);
        $this->request->method('getProject')->willReturn($project);

        $controller = new AdminController(
            $this->request,
            new ConfigurationManager(
                $this->configuration_dao,
                EventDispatcherStub::withIdentityCallback(),
                $this->createMock(DuplicateMilestonesInSidebarConfig::class),
                $this->createMock(UpdateMilestonesInSidebarConfig::class),
            ),
            $this->event_manager,
            new AgileDashboardCrumbBuilder(),
            new AdministrationCrumbBuilder(),
            $this->count_element_mode_checker,
            $this->createStub(ScrumPresenterBuilder::class),
            new TestLayout(new LayoutInspector()),
        );
        $controller->updateConfiguration();
    }

    public function testItDoesNothingIfIsUserNotAdmin(): void
    {
        $this->user = UserTestBuilder::aUser()
            ->withoutSiteAdministrator()
            ->build();

        $this->request->method('exist')->willReturn(true);
        $this->request->method('get')->willReturn(self::PROJECT_ID);
        $GLOBALS['Language']->method('getText')->willReturn('Permission denied');

        $this->configuration_dao->expects($this->never())->method('updateConfiguration');

        $this->update();
    }
}
