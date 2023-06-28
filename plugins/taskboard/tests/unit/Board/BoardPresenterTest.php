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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Taskboard\Column\ColumnPresenter;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\TrackerMappingPresenter;
use Tuleap\Taskboard\Tracker\AssignedToFieldPresenter;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Taskboard\Tracker\TitleFieldPresenter;
use Tuleap\Taskboard\Tracker\TrackerPresenter;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class BoardPresenterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \AgileDashboard_MilestonePresenter&MockObject $milestone_presenter;
    private \Project $project;
    private \Planning_Milestone&MockObject $milestone;

    protected function setUp(): void
    {
        $this->milestone_presenter = $this->createMock(\AgileDashboard_MilestonePresenter::class);
        $this->project             = ProjectTestBuilder::aProject()->withId(101)->build();

        $this->milestone = $this->createMock(\Planning_Milestone::class);
        $this->milestone->method('getProject')->willReturn($this->project);
        $this->milestone->method('getPlanningId')->willReturn(76);
        $this->milestone->method('getArtifactId')->willReturn(89);
    }

    public function testConstructSetsUserIsAdminFlag(): void
    {
        $user = $this->createMock(\PFUser::class);
        $user->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(true);
        $user->method('getPreference')
            ->willReturn('');

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, [], [], true, false);

        self::assertTrue($presenter->user_is_admin);
    }

    public function testConstructSetsTheHiddenItemsDisplayedFlag(): void
    {
        $user = $this->mockNonAdminUser();
        $user->expects(self::once())
            ->method('getPreference')
            ->with('plugin_taskboard_hide_closed_items_89')
            ->willReturn('1');

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, [], [], true, false);

        self::assertFalse($presenter->are_closed_items_displayed);
    }

    public function testConstructJSONEncodesColumnPresenters(): void
    {
        $user = $this->mockNonAdminUser();
        $user->method('getPreference')
            ->willReturn('');

        $todo_column     = new \Cardwall_Column(8, 'To do', 'graffiti-yellow');
        $tracker_mapping = new TrackerMappingPresenter(21, 123, [1456, 1789]);
        $columns         = [new ColumnPresenter($todo_column, false, [$tracker_mapping])];

        $presenter = new BoardPresenter($this->milestone_presenter, $user, $this->milestone, $columns, [], true, false);

        self::assertNotNull($presenter->json_encoded_columns);
    }

    public function testConstructJSONEncodesTrackerStructurePresenters(): void
    {
        $user = $this->mockNonAdminUser();
        $user->method('getPreference')
            ->willReturn('');

        $tracker           = TrackerTestBuilder::aTracker()->withId(96)->build();
        $taskboard_tracker = new TaskboardTracker(TrackerTestBuilder::aTracker()->build(), $tracker);

        $text_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $text_field->method('getId')->willReturn(123);

        $title_field = new TitleFieldPresenter($text_field);

        $selectbox_field = $this->createMock(\Tracker_FormElement_Field_Selectbox::class);
        $selectbox_field->method('getId')->willReturn(124);
        $selectbox_field->method('isMultiple')->willReturn(false);

        $assign_to_field = new AssignedToFieldPresenter($selectbox_field);

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

        self::assertEquals(
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

    private function mockNonAdminUser(): MockObject&\PFUser
    {
        $user = $this->createMock(\PFUser::class);
        $user->expects(self::once())
            ->method('isAdmin')
            ->with(101)
            ->willReturn(false);

        return $user;
    }
}
