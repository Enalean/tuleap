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

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\MediawikiStandalone\Instance\CheckOngoingInitializationStatusStub;
use Tuleap\MediawikiStandalone\Instance\OngoingInitializationStatus;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Request\NotFoundException;
use Tuleap\Templating\TemplateCache;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorPermanentRedirection;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\ProjectByUnixUnixNameFactory;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UnderConstructionControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testUnderConstruction(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('acme')
            ->withPublicName('Acme Project')
            ->build();

        $service = $this->createMock(MediawikiStandaloneService::class);
        $service->method('displayMediawikiHeader');
        $service->method('displayFooter');
        $project->addUsedServices([MediawikiStandaloneService::SERVICE_SHORTNAME, $service]);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::Ongoing),
        );

        ob_start();
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
        self::assertStringContainsString(
            'Building of 😬 Acme Project MediaWiki in progress',
            ob_get_clean(),
        );
    }

    public function testUnderConstructionInError(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('acme')
            ->withPublicName('Acme Project')
            ->build();

        $service = $this->createMock(MediawikiStandaloneService::class);
        $service->method('displayMediawikiHeader');
        $service->method('displayFooter');
        $project->addUsedServices([MediawikiStandaloneService::SERVICE_SHORTNAME, $service]);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::InError),
        );

        ob_start();
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
        self::assertStringContainsString(
            'Build of 😬 Acme Project MediaWiki failed',
            ob_get_clean(),
        );
    }

    public function testExceptionWhenNoProjectIsAskedInTheRequest(): void
    {
        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWithoutProject(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::Ongoing),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            [],
        );
    }

    public function testExceptionWhenNoProject(): void
    {
        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWithoutProject(),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::Ongoing),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
    }

    public function testExceptionWhenProjectIsNotAllowedToUsePlugin(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('acme')
            ->withPublicName('Acme Project')
            ->build();

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            IsProjectAllowedToUsePluginStub::projectIsNotAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::Ongoing),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
    }

    public function testExceptionWhenProjectDoesNotUseMediawikiService(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('acme')
            ->withPublicName('Acme Project')
            ->build();
        $project->addUsedServices();

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::Ongoing),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
    }

    public function testWhenUserRefreshUnderConstructionAndMigrationIsFinishedThenWeShouldRedirectToMediaWikiPages(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withUnixName('acme')
            ->withPublicName('Acme Project')
            ->build();

        $service = $this->createMock(MediawikiStandaloneService::class);
        $service->method('getUrl')->willReturn('/mediawiki/acme');
        $project->addUsedServices([MediawikiStandaloneService::SERVICE_SHORTNAME, $service]);

        $template_cache = $this->createMock(TemplateCache::class);
        $template_cache->method('getPath')->willReturn(null);

        $controller = new UnderConstructionController(
            ProjectByUnixUnixNameFactory::buildWith($project),
            IsProjectAllowedToUsePluginStub::projectIsAllowed(),
            new \TemplateRendererFactory($template_cache),
            CheckOngoingInitializationStatusStub::withStatus(OngoingInitializationStatus::None),
        );

        $this->expectExceptionObject(new LayoutInspectorPermanentRedirection('/mediawiki/acme'));
        $controller->process(
            HTTPRequestBuilder::get()->withUser(UserTestBuilder::buildWithDefaults())->build(),
            LayoutBuilder::build(),
            ['project_name' => 'acme'],
        );
    }
}
