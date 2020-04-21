<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Board;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\Taskboard\Column\ColumnPresenter;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenter;
use Tuleap\Taskboard\Tracker\AssignedToFieldPresenter;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TitleFieldPresenter;
use Tuleap\Taskboard\Tracker\TrackerPresenter;

final class BoardPresenterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var \AgileDashboard_MilestonePresenter|M\LegacyMockInterface|M\MockInterface */
    private $milestone_presenter;
    /** @var M\LegacyMockInterface|M\MockInterface|\Project */
    private $project;
    /** @var M\LegacyMockInterface|M\MockInterface|\Planning_Milestone */
    private $milestone;

    protected function setUp(): void
    {
        $this->milestone_presenter = M::mock(\AgileDashboard_MilestonePresenter::class);
        $this->project             = M::mock(\Project::class);
        $this->project->shouldReceive(['getID' => 101]);
        $this->milestone = M::mock(\Planning_Milestone::class);
        $this->milestone->shouldReceive(['getProject' => $this->project, 'getPlanningId' => 76, 'getArtifactId' => 89]);
    }

    public function testConstructSetsUserIsAdminFlag(): void
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')
            ->with(101)
            ->once()
            ->andReturnTrue();
        $user->shouldReceive('getPreference')
            ->andReturn('');

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, [], [], true, false);

        $this->assertTrue($presenter->user_is_admin);
    }

    public function testConstructSetsTheHiddenItemsDisplayedFlag(): void
    {
        $user = $this->mockNonAdminUser();
        $user->shouldReceive('getPreference')
            ->with('plugin_taskboard_hide_closed_items_89')
            ->once()
            ->andReturn('1');

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, [], [], true, false);

        $this->assertFalse($presenter->are_closed_items_displayed);
    }

    public function testConstructJSONEncodesColumnPresenters(): void
    {
        $user = $this->mockNonAdminUser();
        $user->shouldReceive('getPreference')
            ->andReturn('');

        $todo_column     = new \Cardwall_Column(8, 'To do', 'graffiti-yellow');
        $tracker_mapping = new TrackerMappingPresenter(21, 123, [1456, 1789]);
        $columns         = [new ColumnPresenter($todo_column, false, [$tracker_mapping])];

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, $columns, [], true, false);

        $this->assertNotNull($presenter->json_encoded_columns);
    }

    public function testConstructJSONEncodesTrackerStructurePresenters(): void
    {
        $user = $this->mockNonAdminUser();
        $user->shouldReceive('getPreference')
            ->andReturn('');

        $tracker  = M::mock(Tracker::class);
        $tracker->shouldReceive(['getId' => '96']);
        $taskboard_tracker = new TaskboardTracker(M::mock(Tracker::class), $tracker);
        $title_field       = new TitleFieldPresenter(
            M::mock(\Tracker_FormElement_Field_Text::class)->shouldReceive(['getId' => 123])->getMock()
        );
        $assign_to_field   = new AssignedToFieldPresenter(
            M::mock(\Tracker_FormElement_Field_Selectbox::class)->shouldReceive(['getId' => 124, 'isMultiple' => false])->getMock()
        );

        $trackers = [new TrackerPresenter($taskboard_tracker, true, $title_field, null, $assign_to_field)];

        $presenter = new BoardPresenter(
            $this->milestone_presenter,
            $user,
            $this->milestone,
            [],
            $trackers,
            true,
            false
        );

        $this->assertEquals(
            json_encode([
                [
                    'id' => 96,
                    'can_update_mapped_field' => true,
                    'title_field' => [
                        'id' => 123,
                        'is_string_field' => false,
                    ],
                    'add_in_place' => null,
                    'assigned_to_field' => [
                        'id' => 124,
                        'is_multiple' => false,
                    ],
                ],
            ]),
            $presenter->json_encoded_trackers
        );
    }

    /**
     * @return M\LegacyMockInterface|M\MockInterface|\PFUser
     */
    private function mockNonAdminUser()
    {
        $user = M::mock(\PFUser::class);
        $user->shouldReceive('isAdmin')
            ->with(101)
            ->once()
            ->andReturnFalse();
        return $user;
    }
}
