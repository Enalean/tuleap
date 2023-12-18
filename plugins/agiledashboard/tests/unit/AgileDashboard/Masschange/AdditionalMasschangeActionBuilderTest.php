<?php
/**
 * Copyright (c) Enalean 2019 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Masschange;

use PHPUnit\Framework\MockObject\MockObject;
use Planning;
use Psr\EventDispatcher\EventDispatcherInterface;
use TemplateRenderer;
use Tracker;
use Tuleap\AgileDashboard\BlockScrumAccess;
use Tuleap\AgileDashboard\Stub\VerifyProjectUsesExplicitBacklogStub;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveRootPlanningStub;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;
use Tuleap\Test\Stubs\TemplateRendererStub;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class AdditionalMasschangeActionBuilderTest extends TestCase
{
    private AdditionalMasschangeActionBuilder $builder;

    private VerifyProjectUsesExplicitBacklogStub $explicit_backlog_dao;

    private RetrieveRootPlanningStub $planning_factory;

    private TemplateRenderer $template_renderer;

    private EventDispatcherInterface $event_dispatcher;

    private Tracker|MockObject $tracker;

    private Planning $root_planning;

    protected function setUp(): void
    {
        parent::setUp();

        $this->explicit_backlog_dao = VerifyProjectUsesExplicitBacklogStub::withAlwaysUsesExplicitBacklog();
        $this->template_renderer    = new TemplateRendererStub();
        $this->event_dispatcher     = EventDispatcherStub::withIdentityCallback();

        $project = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker
            ->method('getProject')
            ->willReturn($project);
        $this->tracker
            ->method('getId')
            ->willReturn(149);

        $this->root_planning = PlanningBuilder::aPlanning(101)->withBacklogTrackers($this->tracker)->build();

        $this->planning_factory = RetrieveRootPlanningStub::withProjectAndPlanning(101, $this->root_planning);
    }

    private function buildMasschangeAction(): ?string
    {
        $this->builder = new AdditionalMasschangeActionBuilder(
            $this->explicit_backlog_dao,
            $this->planning_factory,
            $this->template_renderer,
            $this->event_dispatcher,
        );
        $user          = UserTestBuilder::buildWithDefaults();
        return $this->builder->buildMasschangeAction($this->tracker, $user);
    }

    public function testItRendersTheMasschangeAdditionalAction(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $additional_action = $this->buildMasschangeAction();

        $this->assertNotNull($additional_action);
    }

    public function testItReturnsNullIfUserIsNotTrackerAdmin(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(false);

        $additional_action = $this->buildMasschangeAction();
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfProjectDoesNotUseExplicitBacklog(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->explicit_backlog_dao = VerifyProjectUsesExplicitBacklogStub::withNeverUsesExplicitBacklog();

        $additional_action = $this->buildMasschangeAction();
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfProjectDoesNotHaveARootPlanning(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->planning_factory =  RetrieveRootPlanningStub::withProjectAndPlanning(1848, $this->root_planning);

        $additional_action = $this->buildMasschangeAction();
        $this->assertNull($additional_action);
    }

    public function testItReturnsNullIfTrackerNotABacklogTracker(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $not_backlog_tracker    = TrackerTestBuilder::aTracker()->withId(172)->build();
        $this->root_planning    = PlanningBuilder::aPlanning(101)->withBacklogTrackers($not_backlog_tracker)->build();
        $this->planning_factory = RetrieveRootPlanningStub::withProjectAndPlanning(101, $this->root_planning);

        $additional_action = $this->buildMasschangeAction();
        $this->assertNull($additional_action);
    }

    public function testReturnsNullWhenScrumAccessIsBlocked(): void
    {
        $this->tracker
            ->expects(self::once())
            ->method('userIsAdmin')
            ->willReturn(true);

        $this->event_dispatcher = EventDispatcherStub::withCallback(function (object $event) {
            if ($event instanceof BlockScrumAccess) {
                $event->disableScrumAccess();
            }
            return $event;
        });

        $additional_action = $this->buildMasschangeAction();

        self::assertNull($additional_action);
    }
}
