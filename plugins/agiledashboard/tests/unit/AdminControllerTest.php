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

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\GlobalLanguageMock;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;

final class AdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_ID = 123;
    private \Codendi_Request & Stub $request;
    private \AgileDashboard_ConfigurationManager & MockObject $config_manager;
    private \EventManager & Stub $event_manager;
    private CountElementsModeChecker & Stub $count_element_mode_checker;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->request                    = $this->createStub(\Codendi_Request::class);
        $this->config_manager             = $this->createMock(\AgileDashboard_ConfigurationManager::class);
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
            $this->config_manager,
            $this->event_manager,
            $this->createStub(AgileDashboardCrumbBuilder::class),
            $this->createStub(AdministrationCrumbBuilder::class),
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

        $this->request->method('exist')->willReturnMap(['activate-ad-service' => true]);
        $this->request->method('get')->willReturnMap([
            ['activate-ad-service', ''],
            ['group_id', self::PROJECT_ID],
        ]);
        $GLOBALS['Language']->method('getText')->willReturn('Permission denied');

        $this->config_manager->expects(self::never())->method('updateConfiguration');

        $this->update();
    }
}
