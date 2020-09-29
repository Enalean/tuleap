<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\OAuth2Server\SiteAdmin;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\OAuth2Server\App\AppFactory;
use Tuleap\OAuth2Server\App\OAuth2App;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use UserManager;

final class SiteAdminListAppsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UserManager
     */
    private $user_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AppFactory
     */
    private $app_factory;
    /**
     * @var SiteAdminListAppsController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->admin_page_renderer = \Mockery::mock(AdminPageRenderer::class);
        $this->user_manager        = \Mockery::mock(UserManager::class);
        $this->app_factory         = \Mockery::mock(AppFactory::class);
        $this->controller = new SiteAdminListAppsController(
            $this->admin_page_renderer,
            $this->user_manager,
            $this->app_factory
        );
    }

    public function testProcessRendersSomething(): void
    {
        $this->admin_page_renderer->shouldReceive('renderAPresenter')->once();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(true);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->app_factory->shouldReceive('getSiteLevelApps')->andReturn(
            [new OAuth2App(12, 'Site level app', 'https://example.com/redirect', true, null)]
        );

        $this->controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }

    public function testForbidsAccessIfUserIsNotSiteAdministrator(): void
    {
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isSuperUser')->andReturn(false);
        $this->user_manager->shouldReceive('getCurrentUser')->andReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }
}
