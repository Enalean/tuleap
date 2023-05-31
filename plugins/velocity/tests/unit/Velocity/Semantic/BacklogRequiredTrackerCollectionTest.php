<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

use Tracker;
use Tuleap\GlobalLanguageMock;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDone;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneDao;
use Tuleap\Tracker\Semantic\Status\Done\SemanticDoneValueChecker;

final class BacklogRequiredTrackerCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private \PHPUnit\Framework\MockObject\MockObject&BacklogRequiredTrackerCollectionFormatter $formatter;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker_with_done_semantic;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker_without_done_semantic;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker_with_initial_effort_semantic;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $tracker_without_initial_effort_semantic;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $other_tracker_without_done_semantic;
    private Tracker&\PHPUnit\Framework\MockObject\MockObject $other_tracker_without_initial_effort_semantic;

    public function setUp(): void
    {
        parent::setUp();

        $this->formatter = $this->createMock(BacklogRequiredTrackerCollectionFormatter::class);

        $GLOBALS['Language']->method('getLanguageFromAcceptLanguage');

        $this->tracker_with_done_semantic = $this->createMock(Tracker::class);
        $this->tracker_with_done_semantic->method('getName')->willReturn('tracker with done semantic');
        $this->tracker_with_done_semantic->method('getId')->willReturn(1);

        $this->tracker_without_done_semantic = $this->createMock(Tracker::class);
        $this->tracker_without_done_semantic->method('getName')->willReturn('tracker without done semantic');
        $this->tracker_without_done_semantic->method('getId')->willReturn(2);

        $this->tracker_with_initial_effort_semantic = $this->createMock(Tracker::class);
        $this->tracker_with_initial_effort_semantic->method('getName')->willReturn('tracker with initial effort semantic');
        $this->tracker_with_initial_effort_semantic->method('getId')->willReturn(3);

        $this->tracker_without_initial_effort_semantic = $this->createMock(Tracker::class);
        $this->tracker_without_initial_effort_semantic->method('getName')->willReturn('tracker without initial effort semantic');
        $this->tracker_without_initial_effort_semantic->method('getId')->willReturn(4);

        $this->other_tracker_without_done_semantic = $this->createMock(Tracker::class);
        $this->other_tracker_without_done_semantic->method('getName')->willReturn('other tracker without done semantic');
        $this->other_tracker_without_done_semantic->method('getId')->willReturn(5);

        $this->other_tracker_without_initial_effort_semantic = $this->createMock(Tracker::class);
        $this->other_tracker_without_initial_effort_semantic->method('getName')->willReturn('other tracker without initial effort semantic');
        $this->other_tracker_without_initial_effort_semantic->method('getId')->willReturn(4);
    }

    public function testItBuildsACollectionWithTrackersMissingDoneSemantic(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_with_done_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $other_required_tracker->method('getTracker')->willReturn($this->tracker_without_done_semantic);

        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        self::assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        self::assertEquals($collection->getNbMisconfiguredTrackers(), 1);
        self::assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithTrackersMissingInitialEffortSemantic(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_with_initial_effort_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(true);
        $other_required_tracker->method('getTracker')->willReturn($this->tracker_without_initial_effort_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without initial effort semantic');
        self::assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        self::assertEquals($collection->getNbMisconfiguredTrackers(), 1);
        self::assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithMixedMissingSemantics(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_without_done_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(true);
        $other_required_tracker->method('getTracker')->willReturn($this->tracker_without_initial_effort_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);
        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        self::assertEquals($collection->getMisconfiguredBacklogTrackers()[1]->name, 'tracker without initial effort semantic');
        self::assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        self::assertEquals($collection->getNbMisconfiguredTrackers(), 2);
        self::assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithTrackersMissingBothSemantics(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_without_done_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $other_required_tracker->method('getTracker')->willReturn($this->tracker_with_initial_effort_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);
        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        self::assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        self::assertEquals($collection->getNbMisconfiguredTrackers(), 2);
        self::assertTrue($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithWellConfiguredTrackers(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_with_done_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $other_required_tracker->method('getTracker')->willReturn($this->tracker_with_initial_effort_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);
        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        self::assertEquals($collection->getNbMisconfiguredTrackers(), 0);
        self::assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItReturnsDoneLabelWhenAllTrackersMissIt(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $required_tracker->method('getTracker')->willReturn($this->tracker_without_done_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(true);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(false);
        $other_required_tracker->method('getTracker')->willReturn($this->other_tracker_without_done_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);
        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $GLOBALS['Language']->method('getText')->willReturn('Initial effort');
        $semantic_done = new SemanticDone(
            $this->createMock(Tracker::class),
            $this->createMock(\Tracker_Semantic_Status::class),
            $this->createMock(SemanticDoneDao::class),
            $this->createMock(SemanticDoneValueChecker::class),
            []
        );
        self::assertEquals([$semantic_done->getLabel()], $collection->getSemanticMisconfiguredForAllTrackers());
    }

    public function testItReturnsInitialEffortLabelWhenAllTrackersMissIt(): void
    {
        $required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $required_tracker->method('isInitialEffortSemanticMissing')->willReturn(true);
        $required_tracker->method('getTracker')->willReturn($this->tracker_without_initial_effort_semantic);

        $other_required_tracker = $this->createMock(BacklogRequiredTracker::class);
        $other_required_tracker->method('isDoneSemanticMissing')->willReturn(false);
        $other_required_tracker->method('isInitialEffortSemanticMissing')->willReturn(true);
        $other_required_tracker->method('getTracker')->willReturn($this->other_tracker_without_initial_effort_semantic);

        $this->formatter->method('formatTrackerWithoutInitialEffortSemantic')->willReturn([]);
        $this->formatter->method('formatTrackerWithoutDoneSemantic')->willReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        self::assertEquals(['Initial Effort'], $collection->getSemanticMisconfiguredForAllTrackers());
    }
}
