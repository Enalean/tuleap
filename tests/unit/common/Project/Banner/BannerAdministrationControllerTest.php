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

use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;
use Tuleap\Test\Stubs\TemplateRendererStub;

final class BannerAdministrationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const PROJECT_ID = 102;
    private TemplateRendererStub $renderer;
    private BannerRetriever & \PHPUnit\Framework\MockObject\Stub $banner_retriever;

    protected function setUp(): void
    {
        $this->renderer         = new TemplateRendererStub();
        $this->banner_retriever = $this->createStub(BannerRetriever::class);
    }

    private function process(): void
    {
        $layout_helper = new LayoutHelperPassthrough();
        $project       = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $current_user  = UserTestBuilder::buildWithDefaults();
        $layout_helper->setCallbackParams($project, $current_user);

        $request    = HTTPRequestBuilder::get()->build();
        $layout     = new TestLayout(new LayoutInspector());
        $controller = new BannerAdministrationController(
            $layout_helper,
            $this->renderer,
            JavascriptAssetGenericBuilder::build(),
            JavascriptAssetGenericBuilder::build(),
            $this->banner_retriever
        );
        $controller->process($request, $layout, ['project_id' => (string) self::PROJECT_ID]);
    }

    public function testProcessRenders(): void
    {
        $this->banner_retriever->method('getBannerForProject')->willReturn(new Banner('prorelease asymbolia'));

        $this->process();
        self::assertTrue($this->renderer->has_rendered_something);
    }
}
