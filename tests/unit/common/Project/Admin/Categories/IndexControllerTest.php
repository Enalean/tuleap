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

namespace Tuleap\Project\Admin\Categories;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class IndexControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var IndexController */
    private $controller;
    /**
     * @var LayoutHelperPassthrough
     */
    private $layout_helper;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\TroveCatDao
     */
    private $dao;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|IncludeAssets
     */
    private $assets;

    protected function setUp(): void
    {
        $this->layout_helper = new LayoutHelperPassthrough();
        $this->assets        = M::mock(IncludeAssets::class);
        $this->dao           = M::mock(\TroveCatDao::class);
        $this->renderer      = M::mock(\TemplateRenderer::class);
        $this->controller    = new IndexController(
            $this->layout_helper,
            $this->dao,
            $this->renderer,
            $this->assets
        );
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    public function testProcessRenders(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn('102')
            ->getMock();
        $current_user = M::mock(\PFUser::class);
        $this->layout_helper->setCallbackParams($project, $current_user);

        $request = M::mock(\HTTPRequest::class);
        $layout = M::mock(BaseLayout::class);

        $this->assets->shouldReceive('getFileURL')
            ->once()
            ->with('project-admin.js');
        $layout->shouldReceive('includeFooterJavascriptFile')->once();
        $this->renderer->shouldReceive('renderToPage')
            ->once()
            ->with('categories', M::type('array'));
        $this->dao->shouldReceive('getTopCategories')
            ->once()
            ->andReturn([]);

        $this->controller->process($request, $layout, ['id' => '102']);
    }
}
