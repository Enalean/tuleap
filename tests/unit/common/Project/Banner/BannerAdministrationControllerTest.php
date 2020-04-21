<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Project\Banner;

use HTTPRequest;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class BannerAdministrationControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var BannerAdministrationController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $helper;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|\TemplateRenderer */
    private $renderer;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|IncludeAssets */
    private $include_assets;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|BannerRetriever */
    private $banner_retriever;

    protected function setUp(): void
    {
        $this->helper           = new LayoutHelperPassthrough();
        $this->renderer         = Mockery::mock(\TemplateRenderer::class);
        $this->include_assets   = Mockery::mock(IncludeAssets::class);
        $this->banner_retriever = Mockery::mock(BannerRetriever::class);
        $this->controller       = new BannerAdministrationController(
            $this->helper,
            $this->renderer,
            $this->include_assets,
            $this->banner_retriever
        );
    }

    public function testProcessRenders(): void
    {
        $project      = Mockery::mock(Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $current_user = Mockery::mock(PFUser::class);
        $this->helper->setCallbackParams($project, $current_user);

        $request = Mockery::mock(HTTPRequest::class);
        $layout  = Mockery::mock(BaseLayout::class);

        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('ckeditor.js');
        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('project/project-admin-banner.js');
        $layout->shouldReceive('includeFooterJavascriptFile')->twice();
        $this->banner_retriever->shouldReceive('getBannerForProject')
            ->with($project)
            ->once();
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('administration', Mockery::type('array'));

        $this->controller->process($request, $layout, ['id' => '102']);
    }
}
