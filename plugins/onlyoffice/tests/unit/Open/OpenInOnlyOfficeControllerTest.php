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
use Tuleap\Layout\CssAssetGeneric;
use Tuleap\Layout\ThemeVariation;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
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
            ProvideOnlyOfficeDocumentStub::buildWithDocmanFile(
                ProjectTestBuilder::aProject()
                    ->withPublicName('ACME')
                    ->build(),
                new \Docman_File(['item_id' => 147])
            ),
        );

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '147']
        );

        self::assertTrue($template_renderer->has_rendered_something);
    }

    public function testRejectsRequestWhenDocumentCannotBeRetrieved(): void
    {
        $controller = self::buildController(
            new TemplateRendererStub(),
            ProvideOnlyOfficeDocumentStub::buildWithError(),
        );

        $this->expectException(NotFoundException::class);
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '404']
        );
    }

    private static function buildController(\TemplateRenderer $template_renderer, ProvideOnlyOfficeDocument $document_provider): OpenInOnlyOfficeController
    {
        return new OpenInOnlyOfficeController(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            $document_provider,
            $template_renderer,
            new NullLogger(),
            new class implements CssAssetGeneric {
                public function getFileURL(ThemeVariation $variant): string
                {
                    return '';
                }

                public function getIdentifier(): string
                {
                    return '';
                }
            },
            new Prometheus(new CollectorRegistry(new NullStore())),
            '',
        );
    }
}
