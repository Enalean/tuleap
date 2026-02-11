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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\MockObject\Stub;
use Project;
use TemplateRenderer;
use TemplateRendererFactory;
use Tuleap\FRS\LicenseAgreement\LicenseAgreementFactory;
use Tuleap\FRS\LicenseAgreement\NoLicenseToApprove;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ProjectRetriever;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ListLicenseAgreementsControllerTest extends TestCase
{
    private ListLicenseAgreementsController $controller;
    private ProjectRetriever&Stub $project_retriever;
    private Project $project;
    private TemplateRendererFactory&MockObject $renderer_factory;
    private \Tuleap\HTTPRequest $request;
    private PFUser $current_user;
    private LicenseAgreementFactory&Stub $factory;
    private BaseLayout&Stub $layout;
    private LicenseAgreementControllersHelper&Stub $helper;

    #[\Override]
    protected function setUp(): void
    {
        $this->layout       = $this->createStub(BaseLayout::class);
        $this->current_user = new PFUser(['language_id' => 'en_US']);

        $this->request = new \Tuleap\HTTPRequest();
        $this->request->setCurrentUser($this->current_user);

        $this->project           = ProjectTestBuilder::aProject()->withId(101)->build();
        $this->project_retriever = $this->createMock(ProjectRetriever::class);
        $this->project_retriever->expects($this->once())->method('getProjectFromId')
            ->with('101')
            ->willReturn($this->project);

        $this->renderer_factory = $this->createMock(TemplateRendererFactory::class);

        $this->helper = $this->createMock(LicenseAgreementControllersHelper::class);
        $this->helper->expects($this->once())->method('assertCanAccess')->with($this->project, $this->current_user);

        $this->factory = $this->createStub(LicenseAgreementFactory::class);

        $this->controller = new ListLicenseAgreementsController(
            $this->project_retriever,
            $this->helper,
            $this->renderer_factory,
            $this->factory,
            $this->createStub(CSRFSynchronizerToken::class),
        );
    }

    public function testItRendersThePageHeader(): void
    {
        $this->helper->method('renderHeader');

        $content_renderer = $this->createMock(TemplateRenderer::class);
        $content_renderer->expects($this->once())->method('renderToPage')->with('list-license-agreements', self::anything());
        $this->renderer_factory->expects($this->once())->method('getRenderer')->with(self::callback(static function (string $path) {
            return realpath($path) === realpath(__DIR__ . '/../../../../../../src/common/FRS/LicenseAgreement/Admin/templates');
        }))->willReturn($content_renderer);

        $this->layout->method('footer');

        $this->factory->method('getDefaultLicenseAgreementForProject')->willReturn(new NoLicenseToApprove());
        $this->factory->method('getProjectLicenseAgreements')->willReturn([]);

        $this->controller->process($this->request, $this->layout, ['project_id' => '101']);
    }
}
