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
use Tuleap\Layout\BaseLayout;
use Tuleap\OAuth2Server\ProjectAdmin\AdministrationController;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenter;
use Tuleap\OAuth2Server\ProjectAdmin\ProjectAdminPresenterBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class AdministrationControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AdministrationController */
    private $controller;
    /** @var LayoutHelperPassthrough */
    private $layout_helper;
    /** @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer */
    private $renderer;
    /** @var M\LegacyMockInterface|M\MockInterface|ProjectAdminPresenterBuilder */
    private $presenter_builder;

    protected function setUp(): void
    {
        $this->layout_helper     = new LayoutHelperPassthrough();
        $this->renderer          = M::mock(\TemplateRenderer::class);
        $this->presenter_builder = M::mock(ProjectAdminPresenterBuilder::class);
        $this->controller        = new AdministrationController(
            $this->layout_helper,
            $this->renderer,
            $this->presenter_builder,
        );
    }

    public function testProcessRenders(): void
    {
        $project      = M::mock(\Project::class);
        $current_user = M::mock(\PFUser::class);
        $this->layout_helper->setCallbackParams($project, $current_user);

        $request   = M::mock(\HttpRequest::class);
        $layout    = M::mock(BaseLayout::class);
        $presenter = new ProjectAdminPresenter([]);
        $this->presenter_builder->shouldReceive('build')
            ->once()
            ->with($project)
            ->andReturn($presenter);
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('project-admin', $presenter);

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
