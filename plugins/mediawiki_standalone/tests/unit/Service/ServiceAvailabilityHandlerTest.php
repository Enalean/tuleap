<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\MediawikiStandalone\Service;

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ServiceAvailabilityHandlerTest extends TestCase
{
    private ServiceAvailabilityHandler $handler;
    private MediawikiFlavorUsageStub $legacy_mediawiki_usage;

    protected function setUp(): void
    {
        $this->legacy_mediawiki_usage = new MediawikiFlavorUsageStub();
        $this->handler                = new ServiceAvailabilityHandler($this->legacy_mediawiki_usage);
    }

    public function testCannotActivateTheMediawikiServiceWhenTheMediawikiStandaloneServiceIsEnabled(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('usesService')->willReturn(true);
        $service_activation = new StubServiceAvailability('plugin_mediawiki', $project);
        $this->handler->handle($service_activation);

        self::assertTrue($service_activation->cannot_be_activated);
    }

    public function testCannotActivateTheMediawikiStandaloneServiceWhenTheMediawikiServiceIsEnabled(): void
    {
        $project = $this->createStub(\Project::class);
        $project->method('usesService')->willReturn(true);
        $service_activation = new StubServiceAvailability('plugin_mediawiki_standalone', $project);
        $this->handler->handle($service_activation);

        self::assertTrue($service_activation->cannot_be_activated);
    }

    public function testDoesNothingWhenItIsNotAboutMediawikiServices(): void
    {
        $service_activation = new StubServiceAvailability('some_service', ProjectTestBuilder::aProject()->build());
        $this->handler->handle($service_activation);

        self::assertFalse($service_activation->cannot_be_activated);
    }

    public function testCannotActivateMediawikiStandaloneWhenThereIsLegacyMediawikiData(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('usesService')->with('plugin_mediawiki')->willReturn(false);

        $this->legacy_mediawiki_usage->was_legacy_used = true;

        $service_activation = new StubServiceAvailability('plugin_mediawiki_standalone', $project);
        $this->handler->handle($service_activation);

        self::assertTrue($service_activation->cannot_be_activated);
    }

    public function testCannotActivateMediawikiLegacyWhenThereIsMediaWikiStandaloneData(): void
    {
        $project = $this->createMock(\Project::class);
        $project->method('usesService')->with('plugin_mediawiki_standalone')->willReturn(false);

        $this->legacy_mediawiki_usage->was_standalone_used = true;

        $service_activation = new StubServiceAvailability('plugin_mediawiki', $project);
        $this->handler->handle($service_activation);

        self::assertTrue($service_activation->cannot_be_activated);
    }
}
