<?php
/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\FormElement\Burnup\Calculator;

use AgileDashboard_Semantic_InitialEffortFactory;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetFactory;
use Tuleap\AgileDashboard\FormElement\BurnupEffort;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneFactory;
use Tuleap\Tracker\Semantic\Status\TrackerSemanticStatus;
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;
use Tuleap\Tracker\Test\Builders\ChangesetValueFloatTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\FloatFieldBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BurnupEffortCalculatorForArtifactTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private BurnupEffortCalculatorForArtifact $calculator;
    private Tracker_Artifact_ChangesetFactory&\PHPUnit\Framework\MockObject\MockObject $changeset_factory;
    private AgileDashboard_Semantic_InitialEffortFactory&\PHPUnit\Framework\MockObject\MockObject $initial_effort_factory;
    private SemanticDoneFactory&\PHPUnit\Framework\MockObject\MockObject $semantic_done_factory;
    private Artifact $artifact;
    private Tracker_Artifact_Changeset $changeset;
    private \Tracker_FormElement_Field_Float $initial_effort_field;
    private \PHPUnit\Framework\MockObject\MockObject&\AgileDashBoard_Semantic_InitialEffort $semantic_initial_effort;
    private \Tuleap\Tracker\Tracker $tracker;
    private \Tuleap\Tracker\Semantic\Status\Done\SemanticDone&\PHPUnit\Framework\MockObject\MockObject $semantic_done;

    protected function setUp(): void
    {
        $this->changeset_factory      = $this->createMock(Tracker_Artifact_ChangesetFactory::class);
        $this->initial_effort_factory = $this->createMock(AgileDashboard_Semantic_InitialEffortFactory::class);
        $this->semantic_done_factory  = $this->createMock(SemanticDoneFactory::class);

        $this->calculator = new BurnupEffortCalculatorForArtifact(
            $this->changeset_factory,
            $this->initial_effort_factory,
            $this->semantic_done_factory
        );

        $this->tracker                 = TrackerTestBuilder::aTracker()->build();
        $this->artifact                = ArtifactTestBuilder::anArtifact(123)->inTracker($this->tracker)->build();
        $this->changeset               = ChangesetTestBuilder::aChangeset(1)->ofArtifact($this->artifact)->build();
        $this->initial_effort_field    = FloatFieldBuilder::aFloatField(324)->build();
        $this->semantic_done           = $this->createMock(\Tuleap\Tracker\Semantic\Status\Done\SemanticDone::class);
        $this->semantic_initial_effort = $this->createMock(\AgileDashBoard_Semantic_InitialEffort::class);
        $this->initial_effort_factory
            ->method('getByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_initial_effort);
    }

    public function testReturnsZeroEffortWhenChangesetIsNull(): void
    {
        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($this->artifact, 123456789)
            ->willReturn(null);

        $this->semantic_initial_effort
            ->method('getField')
            ->willReturn(null);

        $this->semantic_done_factory
            ->method('getInstanceByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_done);

        $burnup_effort = $this->calculator->getEffort($this->artifact, 123456789);

        $this->assertSame(0.0, $burnup_effort->getTeamEffort());
        $this->assertSame(0.0, $burnup_effort->getTotalEffort());
    }

    public function testReturnsZeroEffortWhenNoInitialEffortField(): void
    {
        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($this->artifact, 123456789)
            ->willReturn($this->changeset);

        $this->semantic_initial_effort
            ->method('getField')
            ->willReturn(null);

        $this->semantic_done_factory
            ->method('getInstanceByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_done);

        $burnup_effort = $this->calculator->getEffort($this->artifact, 123456789);

        $this->assertSame(0.0, $burnup_effort->getTeamEffort());
        $this->assertSame(0.0, $burnup_effort->getTotalEffort());
    }

    public function testCalculatesEffortForOnGoingChangesetAndOnGoingStory(): void
    {
        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($this->artifact, 123456789)
            ->willReturn($this->changeset);

        $this->semantic_initial_effort
            ->method('getField')
            ->willReturn($this->initial_effort_field);

        $this->semantic_done->method('isDone')
            ->with($this->changeset)
            ->willReturn(false);

        $this->semantic_done_factory
            ->method('getInstanceByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_done);

        $changeset_value = ChangesetValueFloatTestBuilder::aValue(123, $this->changeset, $this->initial_effort_field)->withValue(42)->build();
        $this->changeset->setFieldValue($this->initial_effort_field, $changeset_value);

        $user_story_status_semantic = $this->createMock(TrackerSemanticStatus::class);
        TrackerSemanticStatus::setInstance($user_story_status_semantic, $this->tracker);
        $user_story_status_semantic->method('isOpenAtGivenChangeset')
            ->willReturnMap([
                [$this->changeset, false],
            ]);

        $burnup_effort = $this->calculator->getEffort($this->artifact, 123456789);

        $this->assertSame(0.0, $burnup_effort->getTeamEffort());
        $this->assertSame(42.0, $burnup_effort->getTotalEffort());
    }

    public function testCalculatesEffortForOnGoingChangesetAndOnDoneStory(): void
    {
        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($this->artifact, 123456789)
            ->willReturn($this->changeset);

        $this->semantic_initial_effort
            ->method('getField')
            ->willReturn($this->initial_effort_field);

        $this->semantic_done->method('isDone')
            ->with($this->changeset)
            ->willReturn(false);

        $this->semantic_done_factory
            ->method('getInstanceByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_done);

        $changeset_value = new \Tracker_Artifact_ChangesetValue_Float(376, $this->changeset, $this->initial_effort_field, true, 42);
        $this->changeset->setFieldValue($this->initial_effort_field, $changeset_value);

        $user_story_status_semantic = $this->createMock(TrackerSemanticStatus::class);
        TrackerSemanticStatus::setInstance($user_story_status_semantic, $this->tracker);
        $user_story_status_semantic->method('isOpenAtGivenChangeset')
            ->willReturnMap([
                [$this->changeset, true],
            ]);

        $burnup_effort = $this->calculator->getEffort($this->artifact, 123456789);

        $this->assertSame(0.0, $burnup_effort->getTeamEffort());
        $this->assertSame(42.0, $burnup_effort->getTotalEffort());
    }

    public function testCalculatesEffortForDoneChangeset(): void
    {
        $this->changeset_factory
            ->method('getChangesetAtTimestamp')
            ->with($this->artifact, 123456789)
            ->willReturn($this->changeset);

        $this->semantic_initial_effort
            ->method('getField')
            ->willReturn($this->initial_effort_field);

        $this->semantic_done->method('isDone')
            ->with($this->changeset)
            ->willReturn(true);

        $this->semantic_done_factory
            ->method('getInstanceByTracker')
            ->with($this->artifact->getTracker())
            ->willReturn($this->semantic_done);

        $changeset_value = new \Tracker_Artifact_ChangesetValue_Float(376, $this->changeset, $this->initial_effort_field, true, 42);
        $this->changeset->setFieldValue($this->initial_effort_field, $changeset_value);

        $user_story_status_semantic = $this->createMock(TrackerSemanticStatus::class);
        TrackerSemanticStatus::setInstance($user_story_status_semantic, $this->tracker);
        $user_story_status_semantic->method('isOpenAtGivenChangeset')
            ->willReturnMap([
                [$this->changeset, true],
            ]);

        $burnup_effort = $this->calculator->getEffort($this->artifact, 123456789);

        $this->assertInstanceOf(BurnupEffort::class, $burnup_effort);
        $this->assertSame(42.0, $burnup_effort->getTeamEffort());
        $this->assertSame(42.0, $burnup_effort->getTotalEffort());
    }
}
