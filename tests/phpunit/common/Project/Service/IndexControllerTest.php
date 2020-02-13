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

namespace Tuleap\Project\Service;

use HTTPRequest;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class IndexControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration, GlobalLanguageMock;

    /** @var IndexController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $helper;
    /** @var M\LegacyMockInterface|M\MockInterface|ServicesPresenterBuilder */
    private $presenter_builder;
    /** @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer */
    private $renderer;
    /** @var M\LegacyMockInterface|M\MockInterface|IncludeAssets */
    private $include_assets;

    protected function setUp(): void
    {
        $this->helper            = new LayoutHelperPassthrough();
        $this->presenter_builder = M::mock(ServicesPresenterBuilder::class);
        $this->renderer          = M::mock(\TemplateRenderer::class);
        $this->include_assets    = M::mock(IncludeAssets::class);
        $this->controller        = new IndexController(
            $this->helper,
            $this->presenter_builder,
            $this->renderer,
            $this->include_assets,
        );
        $GLOBALS['Language']->shouldReceive('getText')->andReturn('');
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testProcessIncludesSpecialAssetForSiteAdmin(): void
    {
        $project      = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $current_user = M::mock(PFUser::class);
        $current_user->shouldReceive('isSuperUser')->once()->andReturnTrue();
        $this->helper->setCallbackParams($project, $current_user);

        $presenter = M::mock(ServicesPresenter::class);
        $this->presenter_builder->shouldReceive('build')->once()->andReturn($presenter);
        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('site-admin-services.js');
        $request = M::mock(HTTPRequest::class);
        $layout  = M::mock(BaseLayout::class);
        $layout->shouldReceive('includeFooterJavascriptFile')->once();
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('services', $presenter);

        $this->controller->process($request, $layout, ['id' => '102']);
    }

    public function testProcessIncludesNormalAssetForProjectAdmin(): void
    {
        $project      = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $current_user = M::mock(PFUser::class);
        $current_user->shouldReceive('isSuperUser')->once()->andReturnFalse();
        $this->helper->setCallbackParams($project, $current_user);

        $presenter = M::mock(ServicesPresenter::class);
        $this->presenter_builder->shouldReceive('build')->once()->andReturn($presenter);
        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('project-admin-services.js');
        $request = M::mock(HTTPRequest::class);
        $layout  = M::mock(BaseLayout::class);
        $layout->shouldReceive('includeFooterJavascriptFile')->once();
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('services', $presenter);

        $this->controller->process($request, $layout, ['id' => '102']);
    }
}
