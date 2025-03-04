<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\PdfTemplate\Admin;

use Tuleap\GlobalLanguageMock;
use Tuleap\PdfTemplate\Stubs\CSRFTokenProviderStub;
use Tuleap\PdfTemplate\Stubs\RenderAPresenterStub;
use Tuleap\PdfTemplate\Stubs\RetrieveAllImagesStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\User\Avatar\ProvideUserAvatarUrlStub;
use Tuleap\Test\Stubs\User\ForgePermissionsRetrieverStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DisplayPdfTemplateCreationFormControllerTest extends TestCase
{
    use GlobalLanguageMock;

    protected function setUp(): void
    {
        $GLOBALS['Language']
            ->method('getText')
            ->with('system', 'datefmt')
            ->willReturn('d/m/Y H:i');
    }

    public function testExceptionWhenUserIsNotAllowed(): void
    {
        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateCreationFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
            RetrieveAllImagesStub::withoutImages(),
            ProvideUserAvatarUrlStub::build(),
        );

        $user = UserTestBuilder::anActiveUser()->build();

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [],
        );

        self::assertFalse($admin_page_renderer->isCalled());
    }

    public function testOkWhenUserIsSuperUser(): void
    {
        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateCreationFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
            RetrieveAllImagesStub::withoutImages(),
            ProvideUserAvatarUrlStub::build(),
        );

        $user = UserTestBuilder::buildSiteAdministrator();

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [],
        );

        self::assertTrue($admin_page_renderer->isCalled());
    }

    public function testOkWhenUserHasPermissionDelegation(): void
    {
        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateCreationFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withPermission(),
            ),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
            RetrieveAllImagesStub::withoutImages(),
            ProvideUserAvatarUrlStub::build(),
        );

        $user = UserTestBuilder::anActiveUser()->build();

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [],
        );

        self::assertTrue($admin_page_renderer->isCalled());
    }
}
