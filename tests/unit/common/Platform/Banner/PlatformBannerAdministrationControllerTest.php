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

use HTTPRequest;
use Mockery;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ForbiddenException;

final class PlatformBannerAdministrationControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var PlatformBannerAdministrationController */
    private $controller;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdminPageRenderer */
    private $renderer;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|IncludeAssets */
    private $include_assets;
    /** @var Mockery\LegacyMockInterface|Mockery\MockInterface|BannerRetriever */
    private $banner_retriever;

    protected function setUp(): void
    {
        $this->renderer         = Mockery::mock(AdminPageRenderer::class);
        $this->include_assets   = Mockery::mock(IncludeAssets::class);
        $this->banner_retriever = Mockery::mock(BannerRetriever::class);

        $this->controller = new PlatformBannerAdministrationController(
            $this->renderer,
            $this->include_assets,
            $this->banner_retriever
        );
    }

    public function testProcessRenders(): void
    {
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive(['isSuperUser' => true]);

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive(['getCurrentUser' => $current_user]);

        $layout  = Mockery::mock(BaseLayout::class);

        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('ckeditor.js');
        $this->include_assets->shouldReceive('getFileURL')
            ->once()
            ->with('site-admin/platform-banner.js');
        $layout->shouldReceive('includeFooterJavascriptFile')->twice();
        $this->banner_retriever->shouldReceive('getBanner')
            ->once();
        $this->renderer->shouldReceive('renderAPresenter')
            ->once()
            ->with(
                'Platform banner',
                Mockery::type('string'),
                'administration',
                Mockery::type('array')
            );

        $this->controller->process($request, $layout, []);
    }

    public function testThrowExceptionIfUserIsNotSuperUser(): void
    {
        $current_user = Mockery::mock(PFUser::class);
        $current_user->shouldReceive(['isSuperUser' => false]);

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive(['getCurrentUser' => $current_user]);

        $layout  = Mockery::mock(BaseLayout::class);

        $this->expectException(ForbiddenException::class);

        $this->controller->process($request, $layout, []);
    }
}
