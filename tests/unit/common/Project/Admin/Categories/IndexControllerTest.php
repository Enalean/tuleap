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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

final class IndexControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IndexController $controller;
    private LayoutHelperPassthrough $layout_helper;
    private \TroveCatDao&MockObject $dao;
    private \TemplateRenderer&MockObject $renderer;
    private IncludeAssets&MockObject $assets;

    protected function setUp(): void
    {
        $this->layout_helper = new LayoutHelperPassthrough();
        $this->assets        = $this->createMock(IncludeAssets::class);
        $this->dao           = $this->createMock(\TroveCatDao::class);
        $this->renderer      = $this->createMock(\TemplateRenderer::class);
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
        $project      = ProjectTestBuilder::aProject()->withId(102)->build();
        $current_user = UserTestBuilder::aUser()->build();
        $this->layout_helper->setCallbackParams($project, $current_user);

        $request = $this->createMock(\HTTPRequest::class);
        $layout  = $this->createMock(BaseLayout::class);

        $this->assets
            ->expects(self::once())
            ->method('getFileURL')
            ->with('project-admin.js');
        $layout->expects(self::once())->method('includeFooterJavascriptFile');
        $this->renderer
            ->expects(self::once())
            ->method('renderToPage')
            ->with('categories', self::isType('array'));
        $this->dao
            ->expects(self::once())
            ->method('getTopCategories')
            ->willReturn([]);

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
