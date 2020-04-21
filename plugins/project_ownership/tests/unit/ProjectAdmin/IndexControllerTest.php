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

namespace Tuleap\ProjectOwnership\ProjectAdmin;

use HTTPRequest;
use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class IndexControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var IndexController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $helper;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectOwnerPresenterBuilder */
    private $presenter_builder;
    /** @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer */
    private $renderer;

    protected function setUp(): void
    {
        $this->helper            = new LayoutHelperPassthrough();
        $this->presenter_builder = M::mock(ProjectOwnerPresenterBuilder::class);
        $this->renderer          = M::mock(\TemplateRenderer::class);
        $this->controller        = new IndexController(
            $this->helper,
            $this->renderer,
            $this->presenter_builder,
        );
    }

    public function testProcessRenders(): void
    {
        $project      = M::mock(Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $current_user = M::mock(PFUser::class);
        $this->helper->setCallbackParams($project, $current_user);

        $request = M::mock(HTTPRequest::class);
        $layout  = M::mock(BaseLayout::class);
        $layout->shouldReceive('addCssAsset')->once();
        $this->presenter_builder->shouldReceive('build')
            ->once()
            ->with($project);
        $this->renderer->shouldReceive('renderToPage')->once();

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
