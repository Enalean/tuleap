<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement\Admin;

use Tuleap\Request\NotFoundException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\DisplayTrackerLayoutStub;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Tracker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FieldsUsageDisplayControllerTest extends TestCase
{
    use \Tuleap\TemporaryTestDirectory;

    public function testExceptionWhenTrackerIsNotFound(): void
    {
        $controller = new FieldsUsageDisplayController(
            RetrieveTrackerStub::withoutTracker(),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => '123',
            ],
        );
    }

    public function testExceptionWhenUserIsNotAdmin(): void
    {
        $tracker = TrackerTestBuilder::aTracker()
            ->withId(123)
            ->withUserIsAdmin(false)
            ->build();

        $controller = new FieldsUsageDisplayController(
            RetrieveTrackerStub::withTracker($tracker),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => '123',
            ],
        );
    }

    public function testHappyPath(): void
    {
        $tracker = $this->createMock(Tracker::class);
        $tracker->method('getId')->willReturn(123);
        $tracker->method('userIsAdmin')->willReturn(true);
        $tracker->expects($this->once())->method('displayAdminItemHeaderBurningParrot');
        $tracker->expects($this->once())->method('displayFooter');

        $controller = new FieldsUsageDisplayController(
            RetrieveTrackerStub::withTracker($tracker),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
        );

        ob_start();

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            [
                'id' => '123',
            ],
        );

        self::assertStringContainsString('id="tracker-admin-fields-usage-mount-point"', (string) ob_get_clean());
    }
}
