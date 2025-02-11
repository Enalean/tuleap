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

namespace Tuleap\OAuth2Server\Administration\ProjectAdmin;

use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenter;
use Tuleap\OAuth2Server\Administration\AdminOAuth2AppsPresenterBuilder;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class ListAppsControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var ListAppsController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $layout_helper;
    /** @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer */
    private $renderer;
    /** @var \PHPUnit\Framework\MockObject\MockObject&AdminOAuth2AppsPresenterBuilder */
    private $presenter_builder;
    /** @var \CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->layout_helper     = new LayoutHelperPassthrough();
        $this->renderer          = $this->createMock(\TemplateRenderer::class);
        $this->presenter_builder = $this->createMock(AdminOAuth2AppsPresenterBuilder::class);
        $include_assets          = new IncludeViteAssets(__DIR__, 'tests');
        $this->csrf_token        = $this->createMock(\CSRFSynchronizerToken::class);
        $this->controller        = new ListAppsController(
            $this->layout_helper,
            $this->renderer,
            $this->presenter_builder,
            $include_assets,
            $this->csrf_token
        );
    }

    public function testProcessRenders(): void
    {
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_user = UserTestBuilder::aUser()->build();
        $this->layout_helper->setCallbackParams($project, $current_user);

        $request   = HTTPRequestBuilder::get()->build();
        $layout    = LayoutBuilder::build();
        $presenter = AdminOAuth2AppsPresenter::forProjectAdministration($project, [], $this->csrf_token, null);
        $this->presenter_builder->expects(self::once())->method('buildProjectAdministration')
            ->with($this->csrf_token, $project)
            ->willReturn($presenter);
        $this->renderer->expects(self::once())->method('renderToPage')
            ->with('project-admin', $presenter);

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }

    public function testGetUrl(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        self::assertSame('/plugins/oauth2_server/project/102/admin', ListAppsController::getUrl($project));
    }
}
