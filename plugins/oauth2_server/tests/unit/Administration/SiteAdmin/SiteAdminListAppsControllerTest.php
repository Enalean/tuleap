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

namespace Tuleap\OAuth2Server\Administration\SiteAdmin;

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenter;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use UserManager;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class SiteAdminListAppsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&UserManager
     */
    private $user_manager;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AdminOAuth2AppsPresenterBuilder
     */
    private $presenter_builder;
    /**
     * @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var SiteAdminListAppsController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->admin_page_renderer = $this->createMock(AdminPageRenderer::class);
        $this->user_manager        = $this->createMock(UserManager::class);
        $this->presenter_builder   = $this->createMock(AdminOAuth2AppsPresenterBuilder::class);
        $include_assets            = new IncludeViteAssets(__DIR__, 'tests');
        $this->csrf_token          = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller          = new SiteAdminListAppsController(
            $this->admin_page_renderer,
            $this->user_manager,
            $this->presenter_builder,
            $include_assets,
            $this->csrf_token
        );
    }

    public function testProcessRendersSomething(): void
    {
        $this->admin_page_renderer->expects(self::once())->method('renderAPresenter');

        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(true);
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->presenter_builder->expects(self::once())->method('buildSiteAdministration')
            ->with($this->csrf_token)
            ->willReturn(AdminOAuth2AppsPresenter::forSiteAdministration([], $this->csrf_token, null));

        $this->controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }

    public function testForbidsAccessIfUserIsNotSiteAdministrator(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->method('isSuperUser')->willReturn(false);
        $this->user_manager->method('getCurrentUser')->willReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }
}
