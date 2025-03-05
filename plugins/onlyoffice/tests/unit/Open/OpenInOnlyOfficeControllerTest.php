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
use Tuleap\Document\RecentlyVisited\RecordVisit;
use Tuleap\Document\Tests\Stubs\RecentlyVisited\RecordVisitStub;
use Tuleap\Instrument\Prometheus\Prometheus;
use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\JavascriptAssetGenericBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProvideCurrentUserStub;
use Tuleap\Test\Stubs\TemplateRendererStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class OpenInOnlyOfficeControllerTest extends TestCase
{
    public function testDisplaysPage(): void
    {
        $visit = RecordVisitStub::build();

        $template_renderer = new TemplateRendererStub();
        $controller        = self::buildController(
            $template_renderer,
            ProvideOnlyOfficeDocumentStub::buildWithDocmanFile(
                ProjectTestBuilder::aProject()
                    ->withPublicName('ACME')
                    ->build(),
                new \Docman_File(['item_id' => 147])
            ),
            $visit,
        );

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '147']
        );

        self::assertTrue($template_renderer->has_rendered_something);
        self::assertTrue($visit->isSaved());
    }

    public function testRejectsRequestWhenDocumentCannotBeRetrieved(): void
    {
        $visit = RecordVisitStub::build();

        $controller = self::buildController(
            new TemplateRendererStub(),
            ProvideOnlyOfficeDocumentStub::buildWithError(),
            $visit,
        );

        $this->expectException(NotFoundException::class);
        $controller->process(
            HTTPRequestBuilder::get()->build(),
            LayoutBuilder::build(),
            ['id' => '404']
        );

        self::assertFalse($visit->isSaved());
    }

    private static function buildController(
        \TemplateRenderer $template_renderer,
        ProvideOnlyOfficeDocument $document_provider,
        RecordVisit $visit,
    ): OpenInOnlyOfficeController {
        return new OpenInOnlyOfficeController(
            ProvideCurrentUserStub::buildCurrentUserByDefault(),
            $document_provider,
            $template_renderer,
            new NullLogger(),
            JavascriptAssetGenericBuilder::build(),
            new Prometheus(new CollectorRegistry(new NullStore())),
            '',
            $visit,
        );
    }
}
