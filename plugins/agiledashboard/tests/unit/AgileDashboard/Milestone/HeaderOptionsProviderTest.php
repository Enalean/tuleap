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

namespace Tuleap\AgileDashboard\Milestone;

use AgileDashboard_Milestone_Backlog_Backlog;
use AgileDashboard_Milestone_Backlog_BacklogFactory;
use AgileDashboard_PaneInfoIdentifier;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker;
use Tuleap\AgileDashboard\Planning\HeaderOptionsForPlanningProvider;
use Tuleap\Layout\NewDropdown\CurrentContextSectionToHeaderOptionsInserter;
use Tuleap\Tracker\NewDropdown\TrackerNewDropdownLinkPresenterBuilder;

class HeaderOptionsProviderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var HeaderOptionsProvider
     */
    private $provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|HeaderOptionsForPlanningProvider
     */
    private $header_options_for_planning_provider;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\PFUser
     */
    private $user;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|\Planning_Milestone
     */
    private $milestone;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|EventDispatcherInterface
     */
    private $event_dispatcher;
    /**
     * @var AgileDashboard_Milestone_Backlog_Backlog|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $backlog;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ParentTrackerRetriever
     */
    private $parent_retriever;

    protected function setUp(): void
    {
        $backlog_factory        = Mockery::mock(AgileDashboard_Milestone_Backlog_BacklogFactory::class);
        $this->event_dispatcher = Mockery::mock(EventDispatcherInterface::class);
        $this->parent_retriever = Mockery::mock(ParentTrackerRetriever::class);

        $this->header_options_for_planning_provider = Mockery::mock(HeaderOptionsForPlanningProvider::class);

        $this->provider = new HeaderOptionsProvider(
            $backlog_factory,
            new AgileDashboard_PaneInfoIdentifier(),
            new TrackerNewDropdownLinkPresenterBuilder(),
            $this->header_options_for_planning_provider,
            $this->parent_retriever,
            new CurrentContextSectionToHeaderOptionsInserter(),
        );

        $this->user      = Mockery::mock(\PFUser::class);
        $this->milestone = Mockery::mock(\Planning_Milestone::class)
            ->shouldReceive(
                [
                    'getArtifactTitle' => 'Milestone title',
                    'getPlanningId' => 69,
                    'getArtifactId' => 42,
                ]
            )->getMock();

        $this->backlog = Mockery::mock(AgileDashboard_Milestone_Backlog_Backlog::class);
        $backlog_factory
            ->shouldReceive('getBacklog')
            ->with($this->milestone)
            ->andReturn($this->backlog);
    }

    public function testGetHeaderOptionsForPV2(): void
    {
        $this->header_options_for_planning_provider->shouldReceive('addPlanningOptions')->once();
        $this->backlog->shouldReceive(['getDescendantTrackers' => []]);
        $this->parent_retriever->shouldReceive(['getCreatableParentTrackers' => []]);

        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'planning-v2'),
        );
    }

    public function testGetHeaderOptionsForTopPV2(): void
    {
        $this->header_options_for_planning_provider->shouldReceive('addPlanningOptions')->once();
        $this->backlog->shouldReceive(['getDescendantTrackers' => []]);
        $this->parent_retriever->shouldReceive(['getCreatableParentTrackers' => []]);

        self::assertEquals(
            [
                'include_fat_combined' => false,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'topplanning-v2'),
        );
    }

    public function testGetHeaderOptionsForOverview(): void
    {
        $this->backlog->shouldReceive(['getDescendantTrackers' => []]);
        $this->parent_retriever->shouldReceive(['getCreatableParentTrackers' => []]);

        self::assertEquals(
            [
                'include_fat_combined' => true,
                'body_class'           => ['agiledashboard-body'],
            ],
            $this->provider->getHeaderOptions($this->user, $this->milestone, 'details'),
        );
    }

    public function testCurrentContextSectionForMilestone(): void
    {
        $epic = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 101,
                    'getItemName'           => 'epic',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $epic->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => 42, 'link-to-milestone' => '1'])
            ->andReturn('/path/to/101');

        $story = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getItemName'           => 'story',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $story->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => 42, 'link-to-milestone' => '1'])
            ->andReturn('/path/to/102');

        $requirement = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 103,
                    'getItemName'           => 'req',
                    'userCanSubmitArtifact' => false,
                ]
            )
            ->getMock();
        $requirement->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => 42, 'link-to-milestone' => '1'])
            ->andReturn('/path/to/103');

        $task = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 104,
                    'getItemName'           => 'task',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $task->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => 42, 'link-to-milestone' => '1'])
            ->andReturn('/path/to/104');

        $top_requirement = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 105,
                    'getItemName'           => 'top',
                    'userCanSubmitArtifact' => false,
                ]
            )
            ->getMock();
        $top_requirement->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => 42, 'link-to-milestone' => '1'])
            ->andReturn('/path/to/105');

        $this->backlog->shouldReceive(['getDescendantTrackers' => [$story, $requirement, $task]]);
        $this->parent_retriever->shouldReceive(['getCreatableParentTrackers' => [$epic, $top_requirement]]);

        $header_options = $this->provider->getHeaderOptions($this->user, $this->milestone, 'details');
        self::assertEquals('Milestone title', $header_options['new_dropdown_current_context_section']->label);
        self::assertCount(3, $header_options['new_dropdown_current_context_section']->links);
        self::assertEquals('New story', $header_options['new_dropdown_current_context_section']->links[0]->label);
        self::assertEquals('New task', $header_options['new_dropdown_current_context_section']->links[1]->label);
        self::assertEquals('New epic', $header_options['new_dropdown_current_context_section']->links[2]->label);
    }

    public function testCurrentContextSectionForTopBacklog(): void
    {
        $epic = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 101,
                    'getItemName'           => 'epic',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $epic->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => '', 'link-to-milestone' => '1'])
            ->andReturn('/path/to/101');

        $story = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 102,
                    'getSubmitUrl'          => '/path/to/102',
                    'getItemName'           => 'story',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $story->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => '', 'link-to-milestone' => '1'])
            ->andReturn('/path/to/102');

        $requirement = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 103,
                    'getSubmitUrl'          => '/path/to/103',
                    'getItemName'           => 'req',
                    'userCanSubmitArtifact' => false,
                ]
            )
            ->getMock();
        $requirement->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => '', 'link-to-milestone' => '1'])
            ->andReturn('/path/to/103');

        $task = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 104,
                    'getSubmitUrl'          => '/path/to/104',
                    'getItemName'           => 'task',
                    'userCanSubmitArtifact' => true,
                ]
            )
            ->getMock();
        $task->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => '', 'link-to-milestone' => '1'])
            ->andReturn('/path/to/104');

        $top_requirement = Mockery::mock(Tracker::class)
            ->shouldReceive(
                [
                    'getId'                 => 105,
                    'getSubmitUrl'          => '/path/to/105',
                    'getItemName'           => 'top',
                    'userCanSubmitArtifact' => false,
                ]
            )
            ->getMock();
        $top_requirement->shouldReceive('getSubmitUrlWithParameters')
            ->with(['planning[details][69]' => '', 'link-to-milestone' => '1'])
            ->andReturn('/path/to/105');

        $top_milestone = Mockery::mock(\Planning_VirtualTopMilestone::class)
            ->shouldReceive(
                [
                    'getPlanning' => Mockery::mock(\Planning::class)
                        ->shouldReceive(
                            [
                                'getBacklogTrackers' => [$story, $requirement, $task],
                                'getPlanningTracker' => Mockery::mock(Tracker::class)
                                    ->shouldReceive(['userCanSubmitArtifact' => true])
                                    ->getMock(),
                            ]
                        )->getMock(),
                    'getPlanningId' => 69,
                    'getArtifactId' => null,
                ]
            )->getMock();
        $this->parent_retriever->shouldReceive(['getCreatableParentTrackers' => [$epic, $top_requirement]]);

        $header_options = $this->provider->getHeaderOptions($this->user, $top_milestone, 'details');
        self::assertEquals('Top backlog', $header_options['new_dropdown_current_context_section']->label);
        self::assertCount(3, $header_options['new_dropdown_current_context_section']->links);
        self::assertEquals('New story', $header_options['new_dropdown_current_context_section']->links[0]->label);
        self::assertEquals('New task', $header_options['new_dropdown_current_context_section']->links[1]->label);
        self::assertEquals('New epic', $header_options['new_dropdown_current_context_section']->links[2]->label);
    }
}
