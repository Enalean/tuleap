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

namespace ProjectAdmin;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\IncludeAssets;
use Tuleap\OAuth2Server\ProjectAdmin\ListAppsController;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenter;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenterBuilder;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class ListAppsControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var ListAppsController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $layout_helper;
    /** @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer */
    private $renderer;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectAdminPresenterBuilder */
    private $presenter_builder;
    /** @var M\LegacyMockInterface|M\MockInterface|IncludeAssets */
    private $include_assets;
    /** @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->layout_helper     = new LayoutHelperPassthrough();
        $this->renderer          = M::mock(\TemplateRenderer::class);
        $this->presenter_builder = M::mock(ProjectAdminPresenterBuilder::class);
        $this->include_assets    = M::mock(IncludeAssets::class);
        $this->csrf_token        = M::mock(\CSRFSynchronizerToken::class);
        $this->controller        = new ListAppsController(
            $this->layout_helper,
            $this->renderer,
            $this->presenter_builder,
            $this->include_assets,
            $this->csrf_token
        );
    }

    public function testProcessRenders(): void
    {
        $project      = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $current_user = UserTestBuilder::aUser()->build();
        $this->layout_helper->setCallbackParams($project, $current_user);

        $request = HTTPRequestBuilder::get()->build();
        $layout  = LayoutBuilder::build();
        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('project-administration.js');
        $presenter = new ProjectAdminPresenter([], $this->csrf_token, $project, null);
        $this->presenter_builder->shouldReceive('build')
            ->once()
            ->with($this->csrf_token, $project)
            ->andReturn($presenter);
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('project-admin', $presenter);

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }

    public function testGetUrl(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->once()
            ->andReturn(102)
            ->getMock();
        $this->assertSame('/plugins/oauth2_server/project/102/admin', ListAppsController::getUrl($project));
    }
}
