<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\FRS\LicenseAgreement\Admin;

use CSRFSynchronizerToken;
use PFUser;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EditLicenseAgreementControllerTest extends TestCase
{
    private EditLicenseAgreementController $controller;
    private Project&Stub $project;
    private TemplateRendererFactory&Stub $renderer_factory;
    private \Tuleap\HTTPRequest $request;
    private PFUser $current_user;
    private LicenseAgreementFactory&Stub $factory;
    private TestLayout $layout;
    private LicenseAgreementControllersHelper&Stub $helper;
    private ProjectRetriever&Stub $project_retriever;

    #[\Override]
    protected function setUp(): void
    {
        $this->layout = new TestLayout(new LayoutInspector());

        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new \Tuleap\HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project = $this->createConfiguredStub(Project::class, ['getID' => '101']);

        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects($this->once())->method('getProjectFromId')
            ->willReturn($this->project);

        $this->renderer_factory = $this->createStub(TemplateRendererFactory::class);

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->helper->expects($this->once())->method('assertCanAccess')->with($this->project, $this->current_user);
        $this->helper->method('renderHeader');

        $this->factory = $this->createStub(LicenseAgreementFactory::class);

        $assets = $this->createStub(IncludeAssets::class);
        $assets->method('getFileURL');
        $this->controller = new EditLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->renderer_factory,
            $this->factory,
            $this->createStub(CSRFSynchronizerToken::class),
            $assets,
            $assets,
        );
    }

    public function testItRendersThePageWithCustomLicenseAgreement(): void
    {
        $content_renderer = $this->createMock(TemplateRenderer::class);
        $content_renderer->expects($this->once())->method('renderToPage')->with('edit-license-agreement', self::anything());
        $this->renderer_factory->method('getRenderer')->willReturn($content_renderer);

        $license = new LicenseAgreement(1, 'some title', 'some content');
        $this->factory->method('getLicenseAgreementById')->willReturn($license);
        $this->factory->method('canBeDeleted')->willReturn(true);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItRendersTheDefaultSiteAgreementInReadOnly(): void
    {
        $content_renderer = $this->createMock(TemplateRenderer::class);
        $content_renderer->expects($this->once())->method('renderToPage')->with('view-default-license-agreement', self::anything());
        $this->renderer_factory->method('getRenderer')->willReturn($content_renderer);

        $this->factory->method('getLicenseAgreementById')->willReturn(new DefaultLicenseAgreement());

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '0']);
    }

    public function testItThrowAnExceptionWhenTryingToRenderAnInvalidLicense(): void
    {
        $this->expectException(NotFoundException::class);

        $this->factory->method('getLicenseAgreementById')->willReturn(null);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }
}
