<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\FRS\LicenseAgreement\Admin;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ProjectRetriever;

final class AddLicenseAgreementControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var AddLicenseAgreementController */
    private $controller;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\TemplateRenderer
     */
    private $renderer;
    /**
     * @var \CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|IncludeAssets
     */
    private $assets;

    protected function setUp(): void
    {
        $this->project_retriever = M::mock(ProjectRetriever::class);
        $this->helper            = M::mock(LicenseAgreementControllersHelper::class);
        $this->renderer          = M::mock(\TemplateRenderer::class);
        $this->csrf_token        = M::mock(\CSRFSynchronizerToken::class);
        $this->assets            = M::mock(IncludeAssets::class);
        $this->controller        = new AddLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->renderer,
            $this->csrf_token,
            $this->assets
        );
    }

    public function testProcessRenders(): void
    {
        $project = M::mock(\Project::class)->shouldReceive('getID')
            ->andReturn(102)
            ->getMock();
        $this->project_retriever->shouldReceive('getProjectFromId')
            ->with('102')
            ->once()
            ->andReturn($project);
        $current_user = M::mock(\PFUser::class);
        $request      = M::mock(\HTTPRequest::class)->shouldReceive('getCurrentUser')
            ->once()
            ->andReturn($current_user)
            ->getMock();
        $layout       = M::mock(BaseLayout::class);

        $this->helper->shouldReceive('assertCanAccess')
            ->with($project, $current_user)
            ->once();
        $this->assets->shouldReceive('getFileURL')
            ->with('frs-admin-license-agreement.js')
            ->once();
        $layout->shouldReceive('includeFooterJavascriptFile')->once();
        $this->helper->shouldReceive('renderHeader')
            ->with($project)
            ->once();
        $this->renderer->shouldReceive('renderToPage')
            ->with('edit-license-agreement', M::type(EditLicenseAgreementPresenter::class))
            ->once();
        $layout->shouldReceive('footer')->once();

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
