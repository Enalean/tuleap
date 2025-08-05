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

namespace Tuleap\Artidoc\Document;

use Tuleap\Docman\ServiceDocman;
use Tuleap\NeverThrow\Result;
use Tuleap\Plugin\IsProjectAllowedToUsePluginStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class DocumentServiceFromAllowedProjectRetrieverTest extends TestCase
{
    private const PROJECT_ID = 101;

    public function testFaultWhenProjectIsNotAllowed(): void
    {
        $project = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();

        $retriever = new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsNotAllowed());

        self::assertTrue(Result::isErr($retriever->getDocumentServiceFromAllowedProject($project)));
    }

    public function testFaultWhenProjectDoesNotHaveTrackerService(): void
    {
        $project = ProjectTestBuilder::aProject()
            ->withoutServices()
            ->build();

        $retriever = new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed());

        self::assertTrue(Result::isErr($retriever->getDocumentServiceFromAllowedProject($project)));
    }

    public function testFaultWhenProjectDoesNotHaveDocmanService(): void
    {
        $service_tracker = $this->createMock(\ServiceTracker::class);
        $service_tracker->method('getShortName')->willReturn(\trackerPlugin::SERVICE_SHORTNAME);

        $project = ProjectTestBuilder::aProject()
            ->withServices(
                $service_tracker,
            )
            ->build();

        $retriever = new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed());

        self::assertTrue(Result::isErr($retriever->getDocumentServiceFromAllowedProject($project)));
    }

    public function testHappyPath(): void
    {
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

        $retriever = new DocumentServiceFromAllowedProjectRetriever(IsProjectAllowedToUsePluginStub::projectIsAllowed());

        $result = $retriever->getDocumentServiceFromAllowedProject($project);
        self::assertTrue(Result::isOk($result));
        self::assertSame($service_docman, $result->value);
    }
}
