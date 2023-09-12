<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\BacklogPlugin;

use Tuleap\ProgramManagement\ProgramService;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Events\SplitBacklogFeatureFlagEvent;

final class BacklogServiceBlockerTest extends TestCase
{
    private const PROJECT_ID = 134;

    private EventDispatcherStub $event_dispatcher;
    private \Project $project;

    protected function setUp(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withIdentityCallback();

        $this->project = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withoutServices()
            ->build();
    }

    private function shouldBlock(): bool
    {
        $blocker = new BacklogServiceBlocker(
            RetrieveFullProjectStub::withProject($this->project),
            $this->event_dispatcher,
        );
        return $blocker->shouldBacklogServiceBeBlocked(ProjectIdentifierStub::buildWithId(self::PROJECT_ID));
    }

    public function testItReturnsFalseWhenFeatureFlagIsInactive(): void
    {
        self::assertFalse($this->shouldBlock());
    }

    public function testItReturnsFalseWhenProgramServiceIsNotUsed(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(function (SplitBacklogFeatureFlagEvent $event) {
            $event->enableSplitFeatureFlag();
            return $event;
        });
        self::assertFalse($this->shouldBlock());
    }

    public function testItReturnsTrueWhenProgramServiceIsUsedAndFeatureFlagIsActive(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(function (SplitBacklogFeatureFlagEvent $event) {
            $event->enableSplitFeatureFlag();
            return $event;
        });
        $this->project          = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService(ProgramService::SERVICE_SHORTNAME)
            ->build();

        self::assertTrue($this->shouldBlock());
    }
}
