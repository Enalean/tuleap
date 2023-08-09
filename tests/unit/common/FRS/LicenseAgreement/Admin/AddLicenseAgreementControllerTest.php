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

use CSRFSynchronizerToken;
use HTTPRequest;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

final class AddLicenseAgreementControllerTest extends TestCase
{
    private AddLicenseAgreementController $controller;
    /**
     * @var MockObject&ProjectRetriever
     */
    private $project_retriever;
    /**
     * @var MockObject&LicenseAgreementControllersHelper
     */
    private $helper;
    /**
     * @var MockObject&TemplateRenderer
     */
    private $renderer;
    /**
     * @var MockObject&CSRFSynchronizerToken
     */
    private $csrf_token;
    /**
     * @var MockObject&IncludeAssets
     */
    private $assets;

    protected function setUp(): void
    {
        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->helper            = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->renderer          = $this->createMock(TemplateRenderer::class);
        $this->csrf_token        = $this->createMock(CSRFSynchronizerToken::class);
        $this->assets            = $this->createMock(IncludeAssets::class);
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
        $project = $this->createMock(Project::class);
        $project->method('getID')->willReturn(102);
        $this->project_retriever->expects(self::once())->method('getProjectFromId')->with('102')->willReturn($project);
        $current_user = UserTestBuilder::buildWithDefaults();
        $request      = $this->createMock(HTTPRequest::class);
        $request->expects(self::once())->method('getCurrentUser')->willReturn($current_user);
        $layout = $this->createMock(BaseLayout::class);

        $this->helper->expects(self::once())->method('assertCanAccess')->with($project, $current_user);
        $this->assets->expects(self::once())->method('getFileURL')->with('frs-admin-license-agreement.js');
        $layout->expects(self::once())->method('includeFooterJavascriptFile');
        $this->helper->expects(self::once())->method('renderHeader')->with($project);
        $this->renderer->expects(self::once())->method('renderToPage')->with('edit-license-agreement', self::isInstanceOf(EditLicenseAgreementPresenter::class));
        $layout->expects(self::once())->method('footer');

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
