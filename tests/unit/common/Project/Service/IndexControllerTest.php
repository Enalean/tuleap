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

use Tuleap\GlobalLanguageMock;
use Tuleap\Layout\JavascriptAssetGeneric;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Helpers\LayoutHelperPassthrough;
use Tuleap\Test\Stubs\TemplateRendererStub;

final class IndexControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private const PROJECT_ID = 102;

    private TemplateRendererStub $renderer;
    private \PFUser $user;
    private TestLayout $layout;
    private JavascriptAssetGeneric $project_admin_assets;
    private JavascriptAssetGeneric $site_admin_assets;

    protected function setUp(): void
    {
        $this->user                 = UserTestBuilder::buildWithDefaults();
        $this->renderer             = new TemplateRendererStub();
        $this->layout               = new TestLayout(new LayoutInspector());
        $this->project_admin_assets = JavascriptAssetGenericBuilder::build();
        $this->site_admin_assets    = JavascriptAssetGenericBuilder::build();
        $GLOBALS['Language']->method('getText')->willReturn('');
    }

    protected function tearDown(): void
    {
        if (isset($GLOBALS['_SESSION'])) {
            unset($GLOBALS['_SESSION']);
        }
    }

    private function process(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $helper  = new LayoutHelperPassthrough();
        $helper->setCallbackParams($project, $this->user);

        $presenter_builder = $this->createStub(ServicesPresenterBuilder::class);
        $presenter         = $this->createStub(ServicesPresenter::class);
        $presenter_builder->method('build')->willReturn($presenter);

        $request    = HTTPRequestBuilder::get()->build();
        $controller = new IndexController(
            $helper,
            $presenter_builder,
            $this->renderer,
            $this->project_admin_assets,
            $this->site_admin_assets
        );
        $controller->process($request, $this->layout, ['project_id' => (string) self::PROJECT_ID]);
    }

    public function testProcessIncludesSpecialAssetForSiteAdmin(): void
    {
        $this->user = UserTestBuilder::buildSiteAdministrator();
        $this->process();
        self::assertTrue($this->renderer->has_rendered_something);
        self::assertContains($this->site_admin_assets, $this->layout->getJavascriptAssets());
    }

    public function testProcessIncludesNormalAssetForProjectAdmin(): void
    {
        $this->user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $this->process();
        self::assertTrue($this->renderer->has_rendered_something);
        self::assertContains($this->project_admin_assets, $this->layout->getJavascriptAssets());
    }
}
