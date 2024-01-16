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

namespace Tuleap\TrackerCCE\Administration;

use Tuleap\Request\NotFoundException;
use Tuleap\TemporaryTestDirectory;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\TemplateRendererFactoryBuilder;
use Tuleap\Test\Builders\TestLayout;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveTrackerStub;
use Tuleap\Tracker\Test\Stub\Tracker\DisplayTrackerLayoutStub;
use Tuleap\TrackerCCE\WASM\FindWASMModulePath;

final class AdministrationControllerTest extends TestCase
{
    use TemporaryTestDirectory;

    public function testNotFoundWhenTrackerDoesNotExist(): void
    {
        $controller = new AdministrationController(
            RetrieveTrackerStub::withoutTracker(),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            new AdministrationCSRFTokenProvider(),
            new FindWASMModulePath(),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            ['id' => 101],
        );
    }

    public function testNotFoundWhenTrackerIsDeleted(): void
    {
        $controller = new AdministrationController(
            RetrieveTrackerStub::withTracker(
                TrackerTestBuilder::aTracker()->withId(101)->withDeletionDate(1234567890)->build(),
            ),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            new AdministrationCSRFTokenProvider(),
            new FindWASMModulePath(),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            ['id' => 101],
        );
    }

    public function testNotFoundWhenUserIsNotAdminOfTheTracker(): void
    {
        $tracker = $this->createMock(\Tracker::class);
        $tracker->method('getId')->willReturn(101);
        $tracker->method('isDeleted')->willReturn(false);
        $tracker->method('userIsAdmin')->willReturn(false);

        $controller = new AdministrationController(
            RetrieveTrackerStub::withTracker($tracker),
            DisplayTrackerLayoutStub::build(),
            TemplateRendererFactoryBuilder::get()->withPath($this->getTmpDir())->build(),
            new AdministrationCSRFTokenProvider(),
            new FindWASMModulePath(),
        );

        $this->expectException(NotFoundException::class);

        $controller->process(
            HTTPRequestBuilder::get()->build(),
            new TestLayout(new LayoutInspector()),
            ['id' => 101],
        );
    }
}
