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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use TemplateRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class AddLicenseAgreementControllerTest extends TestCase
{
    private AddLicenseAgreementController $controller;
    private ProjectRetriever&Stub $project_retriever;
    private LicenseAgreementControllersHelper&MockObject $helper;
    private TemplateRenderer&MockObject $renderer;
    private CSRFSynchronizerToken&Stub $csrf_token;
    private IncludeAssets&Stub $assets;

    #[\Override]
    protected function setUp(): void
    {
        $this->project_retriever = $this->createStub(ProjectRetriever::class);
        $this->helper            = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->renderer          = $this->createMock(TemplateRenderer::class);
        $this->csrf_token        = $this->createStub(CSRFSynchronizerToken::class);
        $this->assets            = $this->createStub(IncludeAssets::class);
        $this->controller        = new AddLicenseAgreementController(
            $this->project_retriever,
            $this->helper,
            $this->renderer,
            $this->csrf_token,
            $this->assets,
            $this->assets,
        );
    }

    public function testProcessRenders(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(102)->build();
        $this->project_retriever->method('getProjectFromId')->willReturn($project);
        $current_user = UserTestBuilder::buildWithDefaults();
        $request      = $this->createMock(\Tuleap\HTTPRequest::class);
        $request->expects($this->once())->method('getCurrentUser')->willReturn($current_user);
        $layout = $this->createMock(BaseLayout::class);

        $this->helper->expects($this->once())->method('assertCanAccess')->with($project, $current_user);
        $this->assets->method('getFileURL');
        $layout->method('includeFooterJavascriptFile');
        $this->helper->expects($this->once())->method('renderHeader')->with($project);
        $this->renderer->expects($this->once())->method('renderToPage')->with('edit-license-agreement', self::isInstanceOf(EditLicenseAgreementPresenter::class));
        $layout->expects($this->once())->method('footer');

        $this->controller->process($request, $layout, ['project_id' => '102']);
    }
}
