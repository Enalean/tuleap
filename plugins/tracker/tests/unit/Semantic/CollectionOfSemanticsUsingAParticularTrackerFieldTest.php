<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;


#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class CollectionOfSemanticsUsingAParticularTrackerFieldTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const TRACKER_ID = 10;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tuleap\Tracker\Tracker
     */
    private $tracker;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Project
     */
    private $project;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField
     */
    private $field;

    protected function setUp(): void
    {
        $this->field   = $this->createMock(\Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkField::class);
        $this->tracker = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $this->project = $this->createMock(\Project::class);

        $this->field->expects($this->any())->method('getTracker')->willReturn($this->tracker);
        $this->tracker->expects($this->any())->method('getId')->willReturn(self::TRACKER_ID);
        $this->tracker->expects($this->any())->method('getProject')->willReturn($this->project);
        $this->project->expects($this->any())->method('getID')->willReturn(140);
    }

    public function testItReturnsAnEmptyStringWhenThereIsNoSemanticsUsingTheField(): void
    {
        $collection = new CollectionOfSemanticsUsingAParticularTrackerField($this->field, []);
        self::assertEquals('', $collection->getUsages());
    }

    public function testItReturnsUsages(): void
    {
        $tracker_from_same_project = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker_from_same_project->expects($this->any())->method('getId')->willReturn(11);
        $tracker_from_same_project->expects($this->any())->method('getProject')->willReturn($this->project);
        $tracker_from_same_project->expects($this->any())->method('getName')->willReturn('User stories');

        $another_project = $this->createMock(\Project::class);
        $another_project->expects($this->any())->method('getID')->willReturn(150);
        $another_project->expects($this->any())->method('getPublicName')->willReturn('Project X');

        $tracker_from_another_project = $this->createMock(\Tuleap\Tracker\Tracker::class);
        $tracker_from_another_project->expects($this->any())->method('getId')->willReturn(12);
        $tracker_from_another_project->expects($this->any())->method('getProject')->willReturn($another_project);
        $tracker_from_another_project->expects($this->any())->method('getName')->willReturn('Sprints');

        $collection = new CollectionOfSemanticsUsingAParticularTrackerField(
            $this->field,
            [
                $this->getMockedSemantic('Timeframe', $tracker_from_same_project),
                $this->getMockedSemantic('Tooltip', $this->tracker),
                $this->getMockedSemantic('Status', $tracker_from_another_project),
            ]
        );

        $this->assertEquals(
            'Impossible to delete this field (used by: semantic Timeframe of tracker User stories, semantic Tooltip, semantic Status of tracker Sprints in project Project X)',
            $collection->getUsages()
        );
    }

    private function getMockedSemantic(string $semantic_label, \Tuleap\Tracker\Tracker $tracker)
    {
        $semantic = $this->createMock(\Tuleap\Tracker\Semantic\TrackerSemantic::class);
        $semantic->expects($this->any())->method('getLabel')->willReturn($semantic_label);
        $semantic->expects($this->any())->method('getTracker')->willReturn($tracker);

        return $semantic;
    }
}
