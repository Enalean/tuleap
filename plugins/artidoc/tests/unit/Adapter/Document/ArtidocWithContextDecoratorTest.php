<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

namespace Tuleap\Artidoc\Adapter\Document;

use Tuleap\Artidoc\Document\DocumentServiceFromAllowedProjectRetriever;
use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\ProjectByIDFactoryStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ArtidocWithContextDecoratorTest extends TestCase
{
    private const PROJECT_ID = 101;
    private const ITEM_ID    = 12;

    public function testFaultWhenItemIsInAnInvalidProject(): void
    {
        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextDecorator(
            ProjectByIDFactoryStub::buildWithoutProject(),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->decorate($artidoc)));
    }

    public function testFaultWhenProjectIsNotAllowed(): void
    {
        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $retriever = new ArtidocWithContextDecorator(
            ProjectByIDFactoryStub::buildWith(ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build()),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsNotAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->decorate($artidoc)));
    }

    public function testFaultWhenProjectDoesNotHaveTrackerService(): void
    {
        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $project = ProjectTestBuilder::aProject()
            ->withoutServices()
            ->build();

        $retriever = new ArtidocWithContextDecorator(
            ProjectByIDFactoryStub::buildWith($project),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->decorate($artidoc)));
    }

    public function testFaultWhenProjectDoesNotHaveDocmanService(): void
    {
        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $service_tracker = $this->createMock(\ServiceTracker::class);
        $service_tracker->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);

        $project = ProjectTestBuilder::aProject()
            ->withServices(
                $service_tracker,
            )
            ->build();

        $retriever = new ArtidocWithContextDecorator(
            ProjectByIDFactoryStub::buildWith($project),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        self::assertTrue(Result::isErr($retriever->decorate($artidoc)));
    }

    public function testHappyPathRead(): void
    {
        $artidoc = new ArtidocDocument(['group_id' => self::PROJECT_ID, 'item_id' => self::ITEM_ID]);

        $service_tracker = $this->createMock(\ServiceTracker::class);
        $service_tracker->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);

        $service_docman = $this->createMock(ServiceDocman::class);
        $service_docman->method('getShortName')->willReturn(\DocmanPlugin::SERVICE_SHORTNAME);

        $project = ProjectTestBuilder::aProject()
            ->withServices(
                $service_tracker,
                $service_docman,
            )
            ->build();

        $retriever = new ArtidocWithContextDecorator(
            ProjectByIDFactoryStub::buildWith($project),
            new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed()),
        );

        $result = $retriever->decorate($artidoc);
        self::assertTrue(Result::isOk($result));
        self::assertSame($artidoc, $result->value->document);
        self::assertSame($service_docman, $result->value->getContext(ServiceDocman::class));
    }
}
