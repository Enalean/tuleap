<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\Mediawiki\Migration;

use Tuleap\MediawikiStandalone\Service\MediawikiStandaloneService;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ToStandaloneMediawikiRedirectorTest extends TestCase
{
    public function testNoRedirectionWhenStandaloneServiceIsNotUsed(): void
    {
        $inspector = new LayoutInspector();
        $layout    = LayoutBuilder::buildWithInspector($inspector);

        $request = HTTPRequestBuilder::get()->withParam('title', 'My_Page')->build();

        $project = ProjectTestBuilder::aProject()->withoutServices()->build();

        $redirector = new ToStandaloneMediawikiRedirector();
        $redirector->tryRedirection($project, $request, $layout);

        $this->expectNotToPerformAssertions();
    }

    public function testRedirectToCorrespondingPage(): void
    {
        $inspector = new LayoutInspector();
        $layout    = LayoutBuilder::buildWithInspector($inspector);

        $request = HTTPRequestBuilder::get()->withParam('title', 'My_Page')->build();

        $project = ProjectTestBuilder::aProject()->build();

        $standalone = $this->createMock(MediawikiStandaloneService::class);
        $standalone->method('getPageUrl')->willReturn('/mediawiki/acme/My_Page');

        $project->addUsedServices(
            [\MediaWikiPlugin::SERVICE_SHORTNAME, new \ServiceMediawiki($project, [])],
            [MediawikiStandaloneService::SERVICE_SHORTNAME, $standalone],
        );

        $redirector = new ToStandaloneMediawikiRedirector();
        try {
            $redirector->tryRedirection($project, $request, $layout);
            self::fail('Should have been redirectes');
        } catch (LayoutInspectorRedirection $exception) {
            self::assertEquals('/my/redirect.php?return_to=%2Fmediawiki%2Facme%2FMy_Page', $exception->redirect_url);
        }
    }

    public function testRedirectToDefaultWhenNoPageIsAskedInTheRequest(): void
    {
        $inspector = new LayoutInspector();
        $layout    = LayoutBuilder::buildWithInspector($inspector);

        $request = HTTPRequestBuilder::get()->build();

        $project = ProjectTestBuilder::aProject()->build();

        $standalone = $this->createMock(MediawikiStandaloneService::class);
        $standalone->method('getUrl')->willReturn('/mediawiki/acme');

        $project->addUsedServices(
            [\MediaWikiPlugin::SERVICE_SHORTNAME, new \ServiceMediawiki($project, [])],
            [MediawikiStandaloneService::SERVICE_SHORTNAME, $standalone],
        );

        $redirector = new ToStandaloneMediawikiRedirector();
        try {
            $redirector->tryRedirection($project, $request, $layout);
            self::fail('Should have been redirectes');
        } catch (LayoutInspectorRedirection $exception) {
            self::assertEquals('/my/redirect.php?return_to=%2Fmediawiki%2Facme', $exception->redirect_url);
        }
    }
}
