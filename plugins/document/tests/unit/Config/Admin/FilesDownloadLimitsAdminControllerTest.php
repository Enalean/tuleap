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

namespace Tuleap\Document\Config\Admin;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FilesDownloadLimitsAdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private FilesDownloadLimitsAdminController $controller;
    private MockObject&AdminPageRenderer $admin_page_renderer;

    protected function setUp(): void
    {
        $this->admin_page_renderer = $this->createMock(AdminPageRenderer::class);

        $this->controller = new FilesDownloadLimitsAdminController(
            $this->admin_page_renderer,
            new FileDownloadLimitsBuilder(),
            $this->createMock(\CSRFSynchronizerToken::class),
        );
    }

    public function testItThrowExceptionForNonSiteAdminUser(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        $user = $this->createMock(PFUser::class);
        $user->method('isSuperUser')->willReturn(true);

        $this->admin_page_renderer
            ->expects($this->once())
            ->method('renderANoFramedPresenter');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
