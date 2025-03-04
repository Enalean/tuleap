<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Administration;

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\OnlyOffice\DocumentServer\DocumentServer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\DB\UUIDTestContext;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OnlyOfficeAdminSettingsControllerTest extends TestCase
{
    public function testCanDisplaySettingsPage(): void
    {
        $admin_page_renderer = $this->createMock(AdminPageRenderer::class);
        $controller          = self::buildController($admin_page_renderer, UserTestBuilder::buildSiteAdministrator());

        $admin_page_renderer->expects($this->once())->method('renderAPresenter');

        $controller->process($this->createStub(\HTTPRequest::class), LayoutBuilder::build(), []);
    }

    public function testOnlySiteAdministratorsCanAccessThePage(): void
    {
        $controller = self::buildController($this->createStub(AdminPageRenderer::class), UserTestBuilder::anActiveUser()->build());

        $this->expectException(ForbiddenException::class);
        $controller->process($this->createStub(\HTTPRequest::class), LayoutBuilder::build(), []);
    }

    private static function buildController(AdminPageRenderer $admin_page_renderer, \PFUser $current_user): OnlyOfficeAdminSettingsController
    {
        $csrf_store = [];
        return new OnlyOfficeAdminSettingsController(
            $admin_page_renderer,
            ProvideCurrentUserStub::buildWithUser($current_user),
            new OnlyOfficeAdminSettingsPresenter(
                [OnlyOfficeServerPresenter::fromServer(DocumentServer::withoutProjectRestrictions(new UUIDTestContext(), 'https://onlyoffice.example.com/', new ConcealedString('123456')))],
                CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken('/admin', '', $csrf_store)),
            ),
            new IncludeViteAssets(__DIR__ . '/../frontend-assets/', '/assets/onlyoffice'),
        );
    }
}
