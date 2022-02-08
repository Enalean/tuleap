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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Planning;
use Planning_ArtifactMilestone;
use Planning_Milestone;
use Planning_VirtualTopMilestone;
use Tracker;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkPresenter;
use Tuleap\Layout\NewDropdown\NewDropdownLinkSectionPresenter;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class HeaderOptionsForPlanningProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $submilestone_finder;
    /**
     * @var HeaderOptionsForPlanningProvider
     */
    private $provider;

    protected function setUp(): void
    {
        $this->submilestone_finder = Mockery::mock(AgileDashboard_Milestone_Pane_Planning_SubmilestoneFinder::class);

        $this->provider = new HeaderOptionsForPlanningProvider(
            $this->submilestone_finder,
            new TrackerNewDropdownLinkPresenterBuilder(),
            new CurrentContextSectionToHeaderOptionsInserter(),
        );
    }

    public function testItGeneratesBodyClass(): void
    {
        $this->submilestone_finder->shouldReceive('findFirstSubmilestoneTracker')->andReturnNull();

        $options = [];

        $this->provider->addPlanningOptions(
            Mockery::mock(PFUser::class),
            Mockery::mock(Planning_Milestone::class),
            $options
        );

        self::assertEquals(['body_class' => ['has-sidebar-with-pinned-header']], $options);
    }

    public function testItAddsClassToTheExistingOnes(): void
    {
        $this->submilestone_finder->shouldReceive('findFirstSubmilestoneTracker')->andReturnNull();

        $options = ['body_class' => ['blah']];

        $this->provider->addPlanningOptions(
            Mockery::mock(PFUser::class),
            Mockery::mock(Planning_Milestone::class),
            $options
        );

        self::assertEquals(['body_class' => ['blah', 'has-sidebar-with-pinned-header']], $options);
    }

    public function testItAddsSubmilestoneTrackerForMilestoneInNewDropdownCurrentSection(): void
    {
        $user           = Mockery::mock(PFUser::class);
        $sprint_tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'sprint',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $this->submilestone_finder
            ->shouldReceive('findFirstSubmilestoneTracker')
            ->andReturn($sprint_tracker);

        $options = [];
        $this->provider->addPlanningOptions($user, $this->aMilestone(), $options);

        $new_dropdown_current_context_section = $options['new_dropdown_current_context_section'];
        assert($new_dropdown_current_context_section instanceof NewDropdownLinkSectionPresenter);

        self::assertEquals('Milestone title', $new_dropdown_current_context_section->label);
        self::assertCount(1, $new_dropdown_current_context_section->links);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[0]->label);
    }

    public function testItAddsSubmilestoneTrackerForMilestoneInNewDropdownCurrentSectionWithoutOverridingExistingOne(): void
    {
        $user           = Mockery::mock(PFUser::class);
        $sprint_tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'sprint',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $this->submilestone_finder
            ->shouldReceive('findFirstSubmilestoneTracker')
            ->andReturn($sprint_tracker);

        $options = [
            'new_dropdown_current_context_section' => new NewDropdownLinkSectionPresenter(
                "Current section",
                [
                    new NewDropdownLinkPresenter("url", "Already existing link", "icon", []),
                ],
            ),
        ];
        $this->provider->addPlanningOptions($user, $this->aMilestone(), $options);

        $new_dropdown_current_context_section = $options['new_dropdown_current_context_section'];
        assert($new_dropdown_current_context_section instanceof NewDropdownLinkSectionPresenter);

        self::assertEquals('Current section', $new_dropdown_current_context_section->label);
        self::assertCount(2, $new_dropdown_current_context_section->links);
        self::assertEquals('Already existing link', $new_dropdown_current_context_section->links[0]->label);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[1]->label);
    }

    public function testItDoesNotAddSubmilestoneTrackerForMilestoneInNewDropdownCurrentSectionIfUserCannotSubmitArtifacts(): void
    {
        $user           = Mockery::mock(PFUser::class);
        $sprint_tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'sprint',
                    'userCanSubmitArtifact' => false,
                ]
            )
            ->getMock();
        $this->submilestone_finder
            ->shouldReceive('findFirstSubmilestoneTracker')
            ->andReturn($sprint_tracker);

        $options = [];
        $this->provider->addPlanningOptions($user, $this->aMilestone(), $options);

        self::assertFalse(isset($options['new_dropdown_current_context_section']));
    }

    public function testItAddsSubmilestoneTrackerForTopBacklogInNewDropdownCurrentSection(): void
    {
        $user           = Mockery::mock(PFUser::class);
        $sprint_tracker = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'sprint',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();

        $options = [];
        $this->provider->addPlanningOptions($user, $this->theTopBacklogMilestone($sprint_tracker), $options);

        $new_dropdown_current_context_section = $options['new_dropdown_current_context_section'];
        assert($new_dropdown_current_context_section instanceof NewDropdownLinkSectionPresenter);

        self::assertEquals('Top backlog', $new_dropdown_current_context_section->label);
        self::assertCount(1, $new_dropdown_current_context_section->links);
        self::assertEquals('New sprint', $new_dropdown_current_context_section->links[0]->label);
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_ArtifactMilestone
     */
    private function aMilestone()
    {
        return Mockery::mock(Planning_ArtifactMilestone::class)
            ->shouldReceive(['getArtifactTitle' => 'Milestone title'])
            ->getMock();
    }

    /**
     * @return Mockery\LegacyMockInterface|Mockery\MockInterface|Planning_VirtualTopMilestone
     */
    private function theTopBacklogMilestone(Tracker $planning_tracker)
    {
        return Mockery::mock(Planning_VirtualTopMilestone::class)
            ->shouldReceive(
                [
                    'getPlanning' => Mockery::mock(Planning::class)
                        ->shouldReceive(
                            [
                                'getPlanningTracker' => $planning_tracker,
                            ],
                        )->getMock(),
                ],
            )->getMock();
    }
}
