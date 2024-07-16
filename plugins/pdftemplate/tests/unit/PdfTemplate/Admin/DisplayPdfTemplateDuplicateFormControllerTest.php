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

use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Export\Pdf\Template\Identifier\PdfTemplateIdentifierFactory;
use Tuleap\GlobalLanguageMock;
use Tuleap\PdfTemplate\Stubs\CSRFTokenProviderStub;
use Tuleap\PdfTemplate\Stubs\RenderAPresenterStub;
use Tuleap\PdfTemplate\Stubs\RetrieveTemplateStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\Export\Pdf\Template\PdfTemplateTestBuilder;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;
use Tuleap\Test\Stubs\User\ForgePermissionsRetrieverStub;

final class DisplayPdfTemplateDuplicateFormControllerTest extends TestCase
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
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateDuplicateFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
        );

        $user = UserTestBuilder::anActiveUser()->build();

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => $template->identifier->toString(),
            ],
        );

        self::assertFalse($admin_page_renderer->isCalled());
    }

    public function testOkWhenUserIsSuperUser(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateDuplicateFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
        );

        $user = UserTestBuilder::buildSiteAdministrator();

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => $template->identifier->toString(),
            ],
        );

        self::assertTrue($admin_page_renderer->isCalled());
    }

    public function testOkWhenUserHasPermissionDelegation(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateDuplicateFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withPermission(),
            ),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
        );

        $user = UserTestBuilder::anActiveUser()->build();

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => $template->identifier->toString(),
            ],
        );

        self::assertTrue($admin_page_renderer->isCalled());
    }

    public function testExceptionWhenUuidIsInvalid(): void
    {
        $template = PdfTemplateTestBuilder::aTemplate()->build();

        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateDuplicateFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withMatchingTemplate($template),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
        );

        $user = UserTestBuilder::buildSiteAdministrator();

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => 'invaliduuid',
            ],
        );

        self::assertFalse($admin_page_renderer->isCalled());
    }

    public function testExceptionWhenTemplateDoesNotExist(): void
    {
        $identifier_factory = new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory());

        $admin_page_renderer = RenderAPresenterStub::build();

        $controller = new DisplayPdfTemplateDuplicateFormController(
            $admin_page_renderer,
            new UserCanManageTemplatesChecker(
                ForgePermissionsRetrieverStub::withoutPermission(),
            ),
            new PdfTemplateIdentifierFactory(new DatabaseUUIDV7Factory()),
            RetrieveTemplateStub::withoutMatchingTemplate(),
            CSRFTokenProviderStub::withToken(CSRFSynchronizerTokenStub::buildSelf()),
        );

        $user = UserTestBuilder::buildSiteAdministrator();

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser($user,)->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => $identifier_factory->buildIdentifier()->toString(),
            ],
        );

        self::assertFalse($admin_page_renderer->isCalled());
    }
}
