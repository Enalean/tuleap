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
use PFUser;
use Project;
use Tuleap\Layout\BaseLayout;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class IndexControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private IndexController $controller;
    private LayoutHelperPassthrough $helper;
    /**
     * @var ProjectOwnerPresenterBuilder&\PHPUnit\Framework\MockObject\MockObject
     */
    private $presenter_builder;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\TemplateRenderer
     */
    private $renderer;

    #[\Override]
    protected function setUp(): void
    {
        $this->helper            = new LayoutHelperPassthrough();
        $this->presenter_builder = $this->createMock(ProjectOwnerPresenterBuilder::class);
        $this->renderer          = $this->createMock(\TemplateRenderer::class);
        $this->controller        = new IndexController(
            $this->helper,
            $this->renderer,
            $this->presenter_builder,
        );
    }

    public function testProcessRenders(): void
    {
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn('102');

        $current_user = $this->createMock(PFUser::class);
        $this->helper->setCallbackParams($project, $current_user);

        $request = $this->createMock(HTTPRequest::class);
        $layout  = $this->createMock(BaseLayout::class);
        $layout->expects($this->once())->method('addCssAsset');
        $this->presenter_builder->expects($this->once())->method('build')->with($project);
        $this->renderer->expects($this->once())->method('renderToPage');

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
