<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class HistoryEnforcementAdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private HistoryEnforcementAdminController $controller;
    private MockObject&AdminPageRenderer $admin_page_renderer;

    protected function setUp(): void
    {
        $this->admin_page_renderer = $this->createMock(AdminPageRenderer::class);

        $this->controller = new HistoryEnforcementAdminController(
            $this->admin_page_renderer,
            new HistoryEnforcementSettingsBuilder(),
            $this->createMock(\CSRFSynchronizerToken::class),
        );
    }

    public function testItThrowExceptionForNonSiteAdminUser(): void
    {
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->admin_page_renderer
            ->expects(self::once())
            ->method('renderANoFramedPresenter');

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
