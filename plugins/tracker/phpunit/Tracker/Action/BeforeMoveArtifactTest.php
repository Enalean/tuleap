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

namespace Tuleap\Tracker\Action;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Events\MoveArtifactCheckExternalSemantics;

require_once __DIR__ . '/../../bootstrap.php';

class BeforeMoveArtifactTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BeforeMoveArtifact
     */
    private $before_move_artifact;

    protected function setUp()
    {
        parent::setUp();

        $this->event_manager           = Mockery::spy(\EventManager::class);
        $this->status_semantic_checker = Mockery::spy(MoveStatusSemanticChecker::class);
        $this->before_move_artifact    = new BeforeMoveArtifact($this->event_manager, $this->status_semantic_checker);

        $this->source_tracker = Mockery::mock(\Tracker::class);
        $this->target_tracker = Mockery::mock(\Tracker::class);
    }

    public function testSemanticAreAlignedIfBothTrackersHaveBothSemantics()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfSourceTrackersHasBothSemanticsAndTargetHasOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfSourceTrackersHasBothSemanticsAndTargetHasOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfSourceTrackerHasDescriptionAndTargetHasTitle()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfSourceTrackerHasTitleAndTargetHasDescription()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfSourceTrackerHasNoSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testExternalSemanticsAreNotCheckedIfNotReturnedByOtherPlugin()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticsAreNotAlignedIfBothTrackerAndExternalSemanticsAreNotAligne()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            $event->setVisitedByPlugin();
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    public function testSemanticsAreAlignedIfExternalSemanticsAreAlignedAndNotTrackerSemantics()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactCheckExternalSemantics $event) {
            $event->setVisitedByPlugin();
            $event->setExternalSemanticAligned();
            return true;
        }));

        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyStatusSemanticWithFieldsOfSameType()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);



        $this->status_semantic_checker
            ->shouldReceive('areBothSemanticsDefined')
            ->with($this->source_tracker, $this->target_tracker)
            ->once()
            ->andReturns(true);

        $this->status_semantic_checker
            ->shouldReceive('doesBothTrackerStatusFieldHaveTheSameType')
            ->with($this->source_tracker, $this->target_tracker)
            ->once()
            ->andReturns(true);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfBothTrackersHaveOnlyStatusSemanticWithFieldsWithDifferentTypes()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->status_semantic_checker
            ->shouldReceive('areBothSemanticsDefined')
            ->with($this->source_tracker, $this->target_tracker)
            ->once()
            ->andReturns(true);

        $this->status_semantic_checker
            ->shouldReceive('doesBothTrackerStatusFieldHaveTheSameType')
            ->with($this->source_tracker, $this->target_tracker)
            ->once()
            ->andReturns(false);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfOneTrackersDoesNotHaveStatusSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
        ]);

        $this->status_semantic_checker
            ->shouldReceive('areBothSemanticsDefined')
            ->with($this->source_tracker, $this->target_tracker)
            ->once()
            ->andReturns(false);

        $this->status_semantic_checker
            ->shouldReceive('doesBothTrackerStatusFieldHaveTheSameType')
            ->never();

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }
}
