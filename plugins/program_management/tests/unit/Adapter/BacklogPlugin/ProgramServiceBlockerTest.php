<?php
/**
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\ProgramManagement\Adapter\BacklogPlugin;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tuleap\AgileDashboard\Stub\RetrievePlanningStub;
use Tuleap\Option\Option;
use Tuleap\ProgramManagement\Tests\Stub\ProjectIdentifierStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveFullProjectStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUserStub;
use Tuleap\ProgramManagement\Tests\Stub\UserIdentifierStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Tracker\Events\SplitBacklogFeatureFlagEvent;

final class ProgramServiceBlockerTest extends TestCase
{
    private const PROJECT_ID = 101;
    private RetrievePlanningStub $planning_retriever;
    private EventDispatcherInterface $event_dispatcher;
    private \Project $project;

    protected function setUp(): void
    {
        $this->planning_retriever = RetrievePlanningStub::stubNoPlannings();
        $this->event_dispatcher   = EventDispatcherStub::withIdentityCallback();
        $this->project            = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withoutServices()
            ->build();
    }

    /**
     * @return Option<string>
     */
    private function getBlockedMessage(): Option
    {
        $verifier = new ProgramServiceBlocker(
            $this->planning_retriever,
            RetrieveUserStub::withGenericUser(),
            $this->event_dispatcher,
            RetrieveFullProjectStub::withProject($this->project)
        );

        return $verifier->shouldProgramServiceBeBlocked(
            UserIdentifierStub::buildGenericUser(),
            ProjectIdentifierStub::buildWithId(self::PROJECT_ID)
        );
    }

    public function testItBlocksProgramServiceWhenThereIsOneScrumPlanning(): void
    {
        $this->planning_retriever = RetrievePlanningStub::stubAllPlannings();
        self::assertTrue($this->getBlockedMessage()->isValue());
    }

    public function testItDoesNotBlockProgramService(): void
    {
        self::assertFalse($this->getBlockedMessage()->isValue());
    }

    public function testItDoesNotBlockProgramServiceWhenBacklogServiceIsNotUsed(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(function (SplitBacklogFeatureFlagEvent $event) {
            $event->enableSplitFeatureFlag();
            return $event;
        });
        self::assertFalse($this->getBlockedMessage()->isValue());
    }

    public function testItBlocksProgramServiceWhenBacklogServiceIsUsed(): void
    {
        $this->project          = ProjectTestBuilder::aProject()
            ->withId(self::PROJECT_ID)
            ->withUsedService(\AgileDashboardPlugin::PLUGIN_SHORTNAME)
            ->build();
        $this->event_dispatcher = EventDispatcherStub::withCallback(function (SplitBacklogFeatureFlagEvent $event) {
            $event->enableSplitFeatureFlag();
            return $event;
        });

        self::assertTrue($this->getBlockedMessage()->isValue());
    }

    public function testItBlocksProgramServiceWhenBacklogServiceIsNotUsedButThereIsALeftoverScrumPlanning(): void
    {
        $this->event_dispatcher   = EventDispatcherStub::withCallback(function (SplitBacklogFeatureFlagEvent $event) {
            $event->enableSplitFeatureFlag();
            return $event;
        });
        $this->planning_retriever = RetrievePlanningStub::stubAllPlannings();

        self::assertTrue($this->getBlockedMessage()->isValue());
    }
}
