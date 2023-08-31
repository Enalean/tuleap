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

namespace Tuleap\AgileDashboard\Planning;

use Tuleap\AgileDashboard\Stub\VerifyProjectUsesExplicitBacklogStub;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Kanban\CheckSplitKanbanConfiguration;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class VirtualTopMilestonePresenterBuilderTest extends TestCase
{
    private const USER_ID         = 125;
    private const PROJECT_ID      = 172;
    private const ADDITIONAL_PANE = 'zealproof';

    private EventDispatcherStub $event_dispatcher;
    private VerifyProjectUsesExplicitBacklogStub $explicit_backlog_verifier;
    /** @var Option<\Planning_VirtualTopMilestone> */
    private Option $milestone;
    private \PFUser $user;
    private \Project $project;

    protected function setUp(): void
    {
        $this->event_dispatcher          = EventDispatcherStub::withIdentityCallback();
        $this->explicit_backlog_verifier = VerifyProjectUsesExplicitBacklogStub::withAlwaysUsesExplicitBacklog();

        $this->project   = ProjectTestBuilder::aProject()->withId(self::PROJECT_ID)->build();
        $planning        = PlanningBuilder::aPlanning(self::PROJECT_ID)->build();
        $this->milestone = Option::fromValue(new \Planning_VirtualTopMilestone($this->project, $planning));
        $this->user      = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withoutSiteAdministrator()
            ->withMemberOf($this->project)
            ->build();
    }

    private function buildPresenter(): VirtualTopMilestonePresenter
    {
        $builder = new VirtualTopMilestonePresenterBuilder($this->event_dispatcher, $this->explicit_backlog_verifier, new CheckSplitKanbanConfiguration());

        return $builder->buildPresenter($this->milestone, $this->project, $this->user);
    }

    public function testItBuildsPresenterWithVirtualTopBacklogMilestone(): void
    {
        $this->event_dispatcher = EventDispatcherStub::withCallback(
            function (AllowedAdditionalPanesToDisplayCollector $event) {
                $event->add(self::ADDITIONAL_PANE);
                return $event;
            }
        );

        $presenter = $this->buildPresenter();

        $planning_presenter = $presenter->planning_presenter;
        self::assertSame(self::USER_ID, $planning_presenter->user_id);
        self::assertSame(self::PROJECT_ID, $planning_presenter->project_id);
        self::assertSame('ABC', $planning_presenter->milestone_id);
        self::assertTrue($planning_presenter->is_in_explicit_top_backlog);
        self::assertStringContainsString(
            self::ADDITIONAL_PANE,
            $planning_presenter->allowed_additional_panes_to_display
        );
    }

    public function testItMarksFlagWhenBacklogIsNotExplicit(): void
    {
        $this->explicit_backlog_verifier = VerifyProjectUsesExplicitBacklogStub::withNeverUsesExplicitBacklog();

        $presenter = $this->buildPresenter();
        self::assertFalse($presenter->planning_presenter->is_in_explicit_top_backlog);
    }

    public function testItBuildsEmptyPresenterWhenMilestoneIsNothing(): void
    {
        $this->milestone = Option::nothing(\Planning_VirtualTopMilestone::class);
        $this->user      = UserTestBuilder::aUser()
            ->withId(self::USER_ID)
            ->withoutSiteAdministrator()
            ->withAdministratorOf($this->project)
            ->build();

        $presenter = $this->buildPresenter();
        self::assertNull($presenter->planning_presenter);
        self::assertTrue($presenter->is_admin);
    }

    public function testItDoesNotMarkRegularUserAsAdmin(): void
    {
        $this->milestone = Option::nothing(\Planning_VirtualTopMilestone::class);
        self::assertFalse($this->buildPresenter()->is_admin);
    }
}
