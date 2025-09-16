<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\SiteAdmin;

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocAdminSettingsControllerTest extends TestCase
{
    public function testOnlySiteAdministratorsCanAccessThePage(): void
    {
        $user = UserTestBuilder::anActiveUser()->build();

        $admin_page_renderer = $this->createMock(AdminPageRenderer::class);

        $controller = new ArtidocAdminSettingsController(
            $admin_page_renderer,
            ProvideCurrentUserStub::buildWithUser($user),
            new ArtidocAdminSettingsPresenter(
                false,
                CSRFSynchronizerTokenPresenter::fromToken(CSRFSynchronizerTokenStub::buildSelf()),
            ),
        );

        $this->expectException(ForbiddenException::class);
        $controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }

    public function testItDisplayPresenter(): void
    {
        $user = UserTestBuilder::buildSiteAdministrator();

        $admin_page_renderer = $this->createMock(AdminPageRenderer::class);

        $controller = new ArtidocAdminSettingsController(
            $admin_page_renderer,
            ProvideCurrentUserStub::buildWithUser($user),
            new ArtidocAdminSettingsPresenter(
                false,
                CSRFSynchronizerTokenPresenter::fromToken(CSRFSynchronizerTokenStub::buildSelf()),
            ),
        );

        $admin_page_renderer->expects($this->once())->method('renderAPresenter');

        $controller->process(HTTPRequestBuilder::get()->build(), LayoutBuilder::build(), []);
    }
}
