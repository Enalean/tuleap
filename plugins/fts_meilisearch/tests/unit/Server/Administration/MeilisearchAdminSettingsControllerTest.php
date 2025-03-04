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

namespace Tuleap\FullTextSearchMeilisearch\Server\Administration;

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Cryptography\ConcealedString;
use Tuleap\CSRFSynchronizerTokenPresenter;
use Tuleap\FullTextSearchMeilisearch\Server\IProvideCurrentKeyForLocalServer;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class MeilisearchAdminSettingsControllerTest extends TestCase
{
    public function testCanDisplaySettingsPage(): void
    {
        $admin_page_renderer = $this->createMock(AdminPageRenderer::class);

        $controller = self::buildController($admin_page_renderer, UserTestBuilder::buildSiteAdministrator(), false);

        $admin_page_renderer->expects($this->once())->method('renderAPresenter');

        $controller->process($this->createStub(\HTTPRequest::class), LayoutBuilder::build(), []);
    }

    public function testLocalServerDontHaveSettingsPage(): void
    {
        $controller = self::buildController(
            $this->createStub(AdminPageRenderer::class),
            UserTestBuilder::anActiveUser()->build(),
            true
        );

        $this->expectException(ForbiddenException::class);
        $controller->process($this->createStub(\HTTPRequest::class), LayoutBuilder::build(), []);
    }

    public function testOnlySiteAdministratorsCanAccessThePage(): void
    {
        $controller = self::buildController(
            $this->createStub(AdminPageRenderer::class),
            UserTestBuilder::anActiveUser()->build(),
            false
        );

        $this->expectException(ForbiddenException::class);
        $controller->process($this->createStub(\HTTPRequest::class), LayoutBuilder::build(), []);
    }

    private static function buildController(AdminPageRenderer $admin_page_renderer, \PFUser $current_user, bool $is_local_server): MeilisearchAdminSettingsController
    {
        $key = $is_local_server ? new ConcealedString('a') : null;

        $csrf_store = [];
        return new MeilisearchAdminSettingsController(
            new class ($key) implements IProvideCurrentKeyForLocalServer {
                public function __construct(private ?ConcealedString $key)
                {
                }

                public function getCurrentKey(): ?ConcealedString
                {
                    return $this->key;
                }
            },
            $admin_page_renderer,
            ProvideCurrentUserStub::buildWithUser($current_user),
            new MeilisearchAdminSettingsPresenter(
                'https://meilisearch.example.com/',
                true,
                'fts_tuleap',
                CSRFSynchronizerTokenPresenter::fromToken(new \CSRFSynchronizerToken('/admin', '', $csrf_store))
            )
        );
    }
}
