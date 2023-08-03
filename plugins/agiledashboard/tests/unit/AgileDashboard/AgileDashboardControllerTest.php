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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\AgileDashboard\AdminController;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AdministrationCrumbBuilder;
use Tuleap\AgileDashboard\BreadCrumbDropdown\AgileDashboardCrumbBuilder;
use Tuleap\AgileDashboard\FormElement\Burnup\CountElementsModeChecker;
use Tuleap\AgileDashboard\Scrum\ScrumPresenterBuilder;
use Tuleap\ForgeConfigSandbox;
use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Kanban\KanbanFactory;

final class AgileDashboardControllerTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use MockeryPHPUnitIntegration;
    use ForgeConfigSandbox;
    use GlobalResponseMock;
    use GlobalLanguageMock;

    /** @var UserManager */
    private $user_manager;

    /** @var Codendi_Request */
    private $request;

    /** @var PlanningFactory */
    private $planning_factory;

    /** @var KanbanManager */
    private $kanban_manager;

    /** @var AgileDashboard_ConfigurationManager */
    private $config_manager;

    /** @var TrackerFactory */
    private $tracker_factory;

    /** @var KanbanFactory */
    private $kanban_factory;

    /** @var EventManager */
    private $event_manager;

    /** @var AgileDashboardCrumbBuilder */
    private $service_crumb_builder;

    /** @var AdministrationCrumbBuilder */
    private $admin_crumb_builder;

    /**
     * @var Mockery\MockInterface|CountElementsModeChecker
     */
    private $count_element_mode_checker;

    protected function setUp(): void
    {
        ForgeConfig::set('codendi_dir', AGILEDASHBOARD_BASE_DIR . '/../../..');

        $this->user_manager               = Mockery::spy(UserManager::class);
        $this->request                    = Mockery::spy(Codendi_Request::class);
        $this->planning_factory           = Mockery::spy(PlanningFactory::class);
        $this->kanban_manager             = Mockery::spy(KanbanManager::class);
        $this->config_manager             = Mockery::spy(AgileDashboard_ConfigurationManager::class);
        $this->tracker_factory            = Mockery::spy(TrackerFactory::class);
        $this->kanban_factory             = Mockery::spy(KanbanFactory::class);
        $this->event_manager              = Mockery::spy(EventManager::class);
        $this->service_crumb_builder      = Mockery::spy(AgileDashboardCrumbBuilder::class);
        $this->admin_crumb_builder        = Mockery::spy(AdministrationCrumbBuilder::class);
        $this->count_element_mode_checker = Mockery::mock(CountElementsModeChecker::class);

        $this->count_element_mode_checker->shouldReceive('burnupMustUseCountElementsMode')->andReturnFalse();
    }

    public function testItDoesNothingIfIsUserNotAdmin(): void
    {
        $user = Mockery::spy(\PFUser::class)->shouldReceive('isAdmin')->with(123)->andReturns(false)->getMock();

        $this->request->shouldReceive('exist')->with('activate-ad-service')->andReturns(true);
        $this->request->shouldReceive('get')->with('activate-ad-service')->andReturns('');
        $this->request->shouldReceive('get')->with('group_id')->andReturns(123);
        $this->request->shouldReceive('getProject')->andReturns(Mockery::mock(Project::class));
        $this->request->shouldReceive('getCurrentUser')->andReturns($user);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturns($user);

        $controller = new AdminController(
            $this->request,
            $this->planning_factory,
            $this->kanban_manager,
            $this->kanban_factory,
            $this->config_manager,
            $this->tracker_factory,
            $this->event_manager,
            $this->service_crumb_builder,
            $this->admin_crumb_builder,
            $this->count_element_mode_checker,
            Mockery::mock(ScrumPresenterBuilder::class)
        );

        $this->config_manager->shouldReceive('updateConfiguration')->never();

        $controller->updateConfiguration();
    }
}
