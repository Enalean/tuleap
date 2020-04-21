<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Velocity\Semantic;

require_once __DIR__ . '/../../bootstrap.php';

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tuleap\AgileDashboard\Semantic\SemanticDone;

class BacklogRequiredTrackerCollectionTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BacklogRequiredTrackerCollectionFormatter
     */
    private $formatter;
    /**
     * @var Tracker
     */
    private $tracker_with_done_semantic;
    /**
     * @var Tracker
     */
    private $tracker_without_done_semantic;
    /**
     * @var Tracker
     */
    private $tracker_with_initial_effort_semantic;
    /**
     * @var Tracker
     */
    private $tracker_without_initial_effort_semantic;
    /**
     * @var Tracker
     */
    private $other_tracker_without_done_semantic;
    /**
     * @var Tracker
     */
    private $other_tracker_without_initial_effort_semantic;

    public function setUp(): void
    {
        parent::setUp();

        $this->formatter  = Mockery::mock(BacklogRequiredTrackerCollectionFormatter::class);

        $language = Mockery::mock(\BaseLanguage::class);
        $language->shouldReceive('getLanguageFromAcceptLanguage');
        $GLOBALS['Language'] = $language;

        $this->tracker_with_done_semantic = Mockery::mock(Tracker::class);
        $this->tracker_with_done_semantic->shouldReceive('getName')->andReturn('tracker with done semantic');
        $this->tracker_with_done_semantic->shouldReceive('getId')->andReturn(1);

        $this->tracker_without_done_semantic = Mockery::mock(Tracker::class);
        $this->tracker_without_done_semantic->shouldReceive('getName')->andReturn('tracker without done semantic');
        $this->tracker_without_done_semantic->shouldReceive('getId')->andReturn(2);

        $this->tracker_with_initial_effort_semantic = Mockery::mock(Tracker::class);
        $this->tracker_with_initial_effort_semantic->shouldReceive('getName')->andReturn('tracker with initial effort semantic');
        $this->tracker_with_initial_effort_semantic->shouldReceive('getId')->andReturn(3);

        $this->tracker_without_initial_effort_semantic = Mockery::mock(Tracker::class);
        $this->tracker_without_initial_effort_semantic->shouldReceive('getName')->andReturn('tracker without initial effort semantic');
        $this->tracker_without_initial_effort_semantic->shouldReceive('getId')->andReturn(4);

        $this->other_tracker_without_done_semantic = Mockery::mock(Tracker::class);
        $this->other_tracker_without_done_semantic->shouldReceive('getName')->andReturn('other tracker without done semantic');
        $this->other_tracker_without_done_semantic->shouldReceive('getId')->andReturn(5);

        $this->other_tracker_without_initial_effort_semantic = Mockery::mock(Tracker::class);
        $this->other_tracker_without_initial_effort_semantic->shouldReceive('getName')->andReturn('other tracker without initial effort semantic');
        $this->other_tracker_without_initial_effort_semantic->shouldReceive('getId')->andReturn(4);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['Language']);
        parent::tearDown();
    }

    public function testItBuildsACollectionWithTrackersMissingDoneSemantic()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_with_done_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_done_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $this->assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        $this->assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        $this->assertEquals($collection->getNbMisconfiguredTrackers(), 1);
        $this->assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithTrackersMissingInitialEffortSemantic()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_with_initial_effort_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_initial_effort_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $this->assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without initial effort semantic');
        $this->assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        $this->assertEquals($collection->getNbMisconfiguredTrackers(), 1);
        $this->assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithMixedMissingSemantics()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_done_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_initial_effort_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);
        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $this->assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        $this->assertEquals($collection->getMisconfiguredBacklogTrackers()[1]->name, 'tracker without initial effort semantic');
        $this->assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        $this->assertEquals($collection->getNbMisconfiguredTrackers(), 2);
        $this->assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithTrackersMissingBothSemantics()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_done_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_with_initial_effort_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);
        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $this->assertEquals($collection->getMisconfiguredBacklogTrackers()[0]->name, 'tracker without done semantic');
        $this->assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        $this->assertEquals($collection->getNbMisconfiguredTrackers(), 2);
        $this->assertTrue($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItBuildsACollectionWithWellConfiguredTrackers()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_with_done_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_with_initial_effort_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);
        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $this->assertEquals(count($collection->getBacklogRequiredTrackers()), 2);
        $this->assertEquals($collection->getNbMisconfiguredTrackers(), 0);
        $this->assertFalse($collection->areAllBacklogTrackersMisconfigured());
    }

    public function testItReturnsDoneLabelWhenAllTrackersMissIt()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_done_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->other_tracker_without_done_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);
        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $GLOBALS['Language']->shouldReceive('getText')->andReturn('Initial effort');
        $this->assertEquals([SemanticDone::getLabel()], $collection->getSemanticMisconfiguredForAllTrackers());
    }

    public function testItReturnsInitialEffortLabelWhenAllTrackersMissIt()
    {
        $required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(true);
        $required_tracker->shouldReceive('getTracker')->andReturn($this->tracker_without_initial_effort_semantic);

        $other_required_tracker = Mockery::mock(BacklogRequiredTracker::class);
        $other_required_tracker->shouldReceive('isDoneSemanticMissing')->andReturn(false);
        $other_required_tracker->shouldReceive('isInitialEffortSemanticMissing')->andReturn(true);
        $other_required_tracker->shouldReceive('getTracker')->andReturn($this->other_tracker_without_initial_effort_semantic);

        $this->formatter->shouldReceive('formatTrackerWithoutInitialEffortSemantic')->andReturn([]);
        $this->formatter->shouldReceive('formatTrackerWithoutDoneSemantic')->andReturn([]);

        $collection = new BacklogRequiredTrackerCollection($this->formatter);
        $collection->addBacklogRequiredTracker($required_tracker);
        $collection->addBacklogRequiredTracker($other_required_tracker);

        $GLOBALS['Language']->shouldReceive('getText')->andReturn('Initial effort');
        $this->assertEquals(['Initial effort'], $collection->getSemanticMisconfiguredForAllTrackers());
    }
}
