<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\OnlyOffice\Open;

use Enalean\Prometheus\Registry\CollectorRegistry;
use Enalean\Prometheus\Storage\NullStore;
use Psr\Log\NullLogger;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Layout\IncludeViteAssets;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\TemplateRendererStub;

final class OpenInOnlyOfficeControllerTest extends TestCase
{
    public function testDisplaysPage(): void
    {
        $template_renderer = new TemplateRendererStub();
        $controller        = self::buildController(
            $template_renderer,
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(new \Docman_Version(['filename' => 'something.docx'])),
        );

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '147']
        );

        self::assertTrue($template_renderer->has_rendered_something);
    }

    public function testRejectsRequestWhenDocmanVersionCannotBeRetrieved(): void
    {
        $controller = self::buildController(
            new TemplateRendererStub(),
            ProvideDocmanFileLastVersionStub::buildWithError(),
        );

        $this->expectException(NotFoundException::class);
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '404']
        );
    }

    public function testRejectsRequestWhenDocmanVersionCannotBeOpenedWithOnlyOffice(): void
    {
        $controller = self::buildController(
            new TemplateRendererStub(),
            ProvideDocmanFileLastVersionStub::buildWithDocmanVersion(new \Docman_Version(['filename' => 'not_something_onlyoffice_can_open'])),
        );

        $this->expectException(NotFoundException::class);
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '999']
        );
    }

    private static function buildController(\TemplateRenderer $template_renderer, ProvideDocmanFileLastVersion $docman_file_last_version_provider): OpenInOnlyOfficeController
    {
        return new OpenInOnlyOfficeController(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            $docman_file_last_version_provider,
            $template_renderer,
            new NullLogger(),
            new IncludeViteAssets('/', '/'),
            new Prometheus(new CollectorRegistry(new NullStore()))
        );
    }
}
