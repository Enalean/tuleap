<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\Platform\Banner;

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;

final class PlatformBannerAdministrationControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private AdminPageRenderer & \PHPUnit\Framework\MockObject\MockObject $renderer;
    private BannerRetriever & \PHPUnit\Framework\MockObject\Stub $banner_retriever;
    private \PFUser $user;

    protected function setUp(): void
    {
        $this->renderer         = $this->createMock(AdminPageRenderer::class);
        $this->banner_retriever = $this->createStub(BannerRetriever::class);
        $this->user             = UserTestBuilder::buildWithDefaults();
    }

    private function process(): void
    {
        $request    = HTTPRequestBuilder::get()->withUser($this->user)->build();
        $layout     = new TestLayout(new LayoutInspector());
        $controller = new PlatformBannerAdministrationController(
            $this->renderer,
            JavascriptAssetGenericBuilder::build(),
            JavascriptAssetGenericBuilder::build(),
            $this->banner_retriever
        );
        $controller->process($request, $layout, []);
    }

    public function testProcessRenders(): void
    {
        $this->user = UserTestBuilder::buildSiteAdministrator();

        $this->banner_retriever->method('getBanner')
            ->willReturn(new Banner('didymous Politburo', Banner::IMPORTANCE_STANDARD, null));

        $this->renderer->expects(self::once())
            ->method('renderAPresenter')
            ->with(
                'Platform banner',
                self::isType('string'),
                'administration',
                self::isType('array')
            );

        $this->process();
    }

    public function testThrowExceptionIfUserIsNotSuperUser(): void
    {
        $this->user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();
        $this->expectException(ForbiddenException::class);
        $this->process();
    }
}
