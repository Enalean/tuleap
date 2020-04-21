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

use Mockery;
use PHPUnit\Framework\TestCase;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\DefaultLicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreement;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\NotFoundException;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Templating\Mustache\MustacheEngine;

final class EditLicenseAgreementControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var EditLicenseAgreementController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\ServiceFile
     */
    private $service_file;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|TemplateRendererFactory
     */
    private $renderer_factory;
    /**
     * @var \HTTPRequest
     */
    private $request;
    /**
     * @var \PFUser
     */
    private $current_user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementFactory
     */
    private $factory;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectRetriever
     */
    private $project_retriever;

    protected function setUp(): void
    {
        $this->layout = Mockery::mock(BaseLayout::class);

        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->request = new \HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project = Mockery::mock(\Project::class, ['getID' => '101']);
        $this->project->shouldReceive('getFileService')->andReturn($this->service_file)->byDefault();

        $this->project_retriever = \Mockery::mock(ProjectRetriever::class);
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('101')
            ->once()
            ->andReturn($this->project);

        $this->renderer_factory = Mockery::mock(TemplateRendererFactory::class);

        $this->helper = Mockery::mock(LicenseAgreementControllersHelper::class);
        $this->helper->shouldReceive('assertCanAccess')->with($this->project, $this->current_user);
        $this->helper->shouldReceive('renderHeader')->with($this->project);

        $this->factory = Mockery::mock(LicenseAgreementFactory::class);

        $this->controller = new EditLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->renderer_factory,
            $this->factory,
            Mockery::mock(\CSRFSynchronizerToken::class),
            Mockery::spy(IncludeAssets::class),
        );
    }

    public function testItRendersThePageWithCustomLicenseAgreement(): void
    {
        $content_renderer = Mockery::mock(MustacheEngine::class);
        $content_renderer->shouldReceive('renderToPage')->with('edit-license-agreement', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->andReturn($content_renderer);

        $this->layout->shouldReceive('includeFooterJavascriptFile');
        $this->layout->shouldReceive('footer');

        $license = new LicenseAgreement(1, 'some title', 'some content');
        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturn($license);
        $this->factory->shouldReceive('canBeDeleted')->with($this->project, $license)->andReturn(true)->once();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }

    public function testItRendersTheDefaultSiteAgreementInReadOnly(): void
    {
        $content_renderer = Mockery::mock(MustacheEngine::class);
        $content_renderer->shouldReceive('renderToPage')->with('view-default-license-agreement', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->andReturn($content_renderer);

        $this->layout->shouldReceive('includeFooterJavascriptFile');
        $this->layout->shouldReceive('footer');

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 0)->andReturn(new DefaultLicenseAgreement());

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '0']);
    }

    public function testItThrowAnExceptionWhenTryingToRenderAnInvalidLicense(): void
    {
        $this->expectException(NotFoundException::class);

        $this->factory->shouldReceive('getLicenseAgreementById')->with($this->project, 1)->andReturnNull();

        $this->controller->process($this->request, $this->layout, ['project_id' => '101', 'id' => '1']);
    }
}
