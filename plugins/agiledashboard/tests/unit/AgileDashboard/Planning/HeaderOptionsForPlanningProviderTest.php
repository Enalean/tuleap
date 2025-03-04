<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

use AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder;
use PHPUnit\Framework\MockObject\MockObject;
use Planning_ArtifactMilestone;
use Planning_VirtualTopMilestone;
use Tracker;
use Tuleap\AgileDashboard\Test\Builders\PlanningBuilder;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Option\Option;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;
use Tuleap\Tracker\Permission\VerifySubmissionPermissions;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HeaderOptionsForPlanningProviderTest extends TestCase
{
    private AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder&MockObject $submilestone_finder;
    private VerifySubmissionPermissions&MockObject $submission_permissions_verifier;
    private HeaderOptionsForPlanningProvider $provider;

    protected function setUp(): void
    {
        $this->submilestone_finder             = $this->createMock(AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder::class);
        $this->submission_permissions_verifier = $this->createMock(VerifySubmissionPermissions::class);

        $this->provider = new HeaderOptionsForPlanningProvider(
            $this->submilestone_finder,
            new TrackerNewDropdownLinkPresenterBuilder(),
            new CurrentContextSectionToHeaderOptionsInserter(),
            $this->submission_permissions_verifier,
        );
    }

    public function testItAddsSubmilestoneTrackerForMilestoneInNewDropdownCurrentSection(): void
    {
        $user           = UserTestBuilder::buildWithDefaults();
        $sprint_tracker = TrackerTestBuilder::aTracker()
            ->withId(102)
            ->withShortName('sprint')
            ->build();
        $this->submilestone_finder->method('findFirstSubmilestoneTracker')->willReturn($sprint_tracker);
        $this->submission_permissions_verifier->method('canUserSubmitArtifact')->with($user, $sprint_tracker)->willReturn(true);

        $new_dropdown_current_context_section = $this->provider
            ->getCurrentContextSection($user, $this->aMilestone(), Option::nothing(NewDropdownLinkSectionPresenter::class))
            ->unwrapOr(null);

        self::assertEquals('Milestone title', $new_dropdown_current_context_section->label);
        self::assertCount(1, $new_dropdown_current_context_section->links);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[0]->label);
    }

    public function testItAddsSubmilestoneTrackerForMilestoneInNewDropdownCurrentSectionWithoutOverridingExistingOne(): void
    {
        $user           = UserTestBuilder::buildWithDefaults();
        $sprint_tracker = TrackerTestBuilder::aTracker()
            ->withId(102)
            ->withShortName('sprint')
            ->build();
        $this->submilestone_finder->method('findFirstSubmilestoneTracker')->willReturn($sprint_tracker);
        $this->submission_permissions_verifier->method('canUserSubmitArtifact')->with($user, $sprint_tracker)->willReturn(true);

        $existing_section                     = Option::fromValue(new NewDropdownLinkSectionPresenter(
            'Current section',
            [new NewDropdownLinkPresenter('url', 'Already existing link', 'icon', [])],
        ));
        $new_dropdown_current_context_section = $this->provider
            ->getCurrentContextSection($user, $this->aMilestone(), $existing_section)
            ->unwrapOr(null);

        self::assertEquals('Current section', $new_dropdown_current_context_section->label);
        self::assertCount(2, $new_dropdown_current_context_section->links);
        self::assertEquals('Already existing link', $new_dropdown_current_context_section->links[0]->label);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[1]->label);
    }

    public function testItDoesNotAddSubmilestoneTrackerForMilestoneInNewDropdownCurrentSectionIfUserCannotSubmitArtifacts(): void
    {
        $user           = UserTestBuilder::buildWithDefaults();
        $sprint_tracker = TrackerTestBuilder::aTracker()
            ->withId(102)
            ->withShortName('sprint')
            ->build();
        $this->submilestone_finder->method('findFirstSubmilestoneTracker')->willReturn($sprint_tracker);
        $this->submission_permissions_verifier->method('canUserSubmitArtifact')->with($user, $sprint_tracker)->willReturn(false);

        self::assertTrue(
            $this->provider
                ->getCurrentContextSection($user, $this->aMilestone(), Option::nothing(NewDropdownLinkSectionPresenter::class))
                ->isNothing()
        );
    }

    public function testItAddsSubmilestoneTrackerForTopBacklogInNewDropdownCurrentSection(): void
    {
        $user           = UserTestBuilder::buildWithDefaults();
        $sprint_tracker = TrackerTestBuilder::aTracker()
            ->withId(102)
            ->withShortName('sprint')
            ->build();
        $this->submission_permissions_verifier->method('canUserSubmitArtifact')->with($user, $sprint_tracker)->willReturn(true);

        $new_dropdown_current_context_section = $this->provider
            ->getCurrentContextSection(
                $user,
                $this->theTopBacklogMilestone($sprint_tracker),
                Option::nothing(NewDropdownLinkSectionPresenter::class)
            )->unwrapOr(null);

        self::assertEquals('Top backlog', $new_dropdown_current_context_section->label);
        self::assertCount(1, $new_dropdown_current_context_section->links);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[0]->label);
    }

    private function aMilestone(): Planning_ArtifactMilestone&MockObject
    {
        $milestone = $this->createMock(Planning_ArtifactMilestone::class);
        $milestone->method('getArtifactTitle')->willReturn('Milestone title');

        return $milestone;
    }

    private function theTopBacklogMilestone(Tracker $planning_tracker): Planning_VirtualTopMilestone&MockObject
    {
        $milestone = $this->createMock(Planning_VirtualTopMilestone::class);
        $milestone->method('getPlanning')->willReturn(PlanningBuilder::aPlanning(101)->withMilestoneTracker($planning_tracker)->build());

        return $milestone;
    }
}
