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
use ProjectManager;
use TemplateRendererFactory;
use Tuleap\FRS\FRSPermissionManager;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\NoLicenseToApprove;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;
use Tuleap\Templating\Mustache\MustacheEngine;

class ListLicenseAgreementsControllerTest extends TestCase
{
    use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var ListLicenseAgreementsController
     */
    private $controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ProjectManager
     */
    private $project_manager;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Project
     */
    private $project;
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

    protected function setUp(): void
    {
        $this->layout       = Mockery::mock(BaseLayout::class);
        $this->current_user = new \PFUser(['language_id' => 'en_US']);

        $this->request = new \HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project_manager = Mockery::mock(ProjectManager::class);
        $this->project = Mockery::mock(\Project::class, ['isError' => false, 'getID' => '101']);
        $this->project_manager->shouldReceive('getProject')->with('101')->andReturns($this->project);

        $this->renderer_factory = Mockery::mock(TemplateRendererFactory::class);

        $this->helper = Mockery::mock(LicenseAgreementControllersHelper::class);
        $this->helper->shouldReceive('assertCanAccess')->with($this->project, $this->current_user);

        $this->factory = Mockery::mock(LicenseAgreementFactory::class);

        $this->controller = new ListLicenseAgreementsController(
            $this->project_manager,
            $this->helper,
            $this->renderer_factory,
            $this->factory,
            Mockery::mock(\CSRFSynchronizerToken::class),
        );
    }

    public function testItRendersThePageHeader(): void
    {
        $this->helper->shouldReceive('renderHeader')->with($this->project);

        $content_renderer = Mockery::mock(MustacheEngine::class);
        $content_renderer->shouldReceive('renderToPage')->with('list-license-agreements', Mockery::any())->once();
        $this->renderer_factory->shouldReceive('getRenderer')->with(Mockery::on(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->andReturn($content_renderer);

        $this->layout->shouldReceive('footer');

        $this->factory->shouldReceive('getDefaultLicenseAgreementForProject')->andReturns(new NoLicenseToApprove());
        $this->factory->shouldReceive('getProjectLicenseAgreements')->with($this->project)->andReturn([]);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
