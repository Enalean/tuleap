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
use HTTPRequest;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ServiceFile;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\PHPUnit\TestCase;

final class EditLicenseAgreementControllerTest extends TestCase
{
    private EditLicenseAgreementController $controller;
    /**
     * @var MockObject&Project
     */
    private $project;
    /**
     * @var MockObject&ServiceFile
     */
    private $service_file;
    /**
     * @var MockObject&TemplateRendererFactory
     */
    private $renderer_factory;
    private HTTPRequest $request;
    private PFUser $current_user;
    /**
     * @var MockObject&LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var MockObject&BaseLayout
     */
    private $layout;
    /**
     * @var MockObject&LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var MockObject&ProjectRetriever
     */
    private $project_retriever;

    protected function setUp(): void
    {
        $this->layout = $this->createMock(BaseLayout::class);

        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project = $this->createConfiguredMock(Project::class, ['getID' => '101']);
        $this->project->method('getFileService')->willReturn($this->service_file);

        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects(self::once())->method('getProjectFromId')
            ->with('101')
            ->willReturn($this->project);

        $this->renderer_factory = $this->createMock(TemplateRendererFactory::class);

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->helper->method('assertCanAccess')->with($this->project, $this->current_user);
        $this->helper->method('renderHeader')->with($this->project);

        $this->factory = $this->createMock(LicenseAgreementFactory::class);

        $assets = $this->createMock(IncludeAssets::class);
        $assets->method('getFileURL');
        $this->controller = new EditLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->renderer_factory,
            $this->factory,
            $this->createMock(CSRFSynchronizerToken::class),
            $assets,
        );
    }

    public function testItRendersThePageWithCustomLicenseAgreement(): void
    {
        $content_renderer = $this->createMock(TemplateRenderer::class);
        $content_renderer->expects(self::once())->method('renderToPage')->with('edit-license-agreement', self::anything());
        $this->renderer_factory->method('getRenderer')->with(self::callback(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->willReturn($content_renderer);

        $this->layout->method('includeFooterJavascriptFile');
        $this->layout->method('footer');

        $license = new LicenseAgreement(1, 'some title', 'some content');
        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn($license);
        $this->factory->expects(self::once())->method('canBeDeleted')->with($this->project, $license)->willReturn(true);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItRendersTheDefaultSiteAgreementInReadOnly(): void
    {
        $content_renderer = $this->createMock(TemplateRenderer::class);
        $content_renderer->expects(self::once())->method('renderToPage')->with('view-default-license-agreement', self::anything());
        $this->renderer_factory->method('getRenderer')->with(self::callback(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->willReturn($content_renderer);

        $this->layout->method('includeFooterJavascriptFile');
        $this->layout->method('footer');

        $this->factory->method('getLicenseAgreementById')->with($this->project, 0)->willReturn(new DefaultLicenseAgreement());

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '0']);
    }

    public function testItThrowAnExceptionWhenTryingToRenderAnInvalidLicense(): void
    {
        self::expectException(NotFoundException::class);

        $this->factory->method('getLicenseAgreementById')->with($this->project, 1)->willReturn(null);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }
}
