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
use Tracker_FormElement_Field_List;
use Tracker_FormElementFactory;
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

        $this->form_element_factory = Mockery::mock(Tracker_FormElementFactory::class);

        $this->event_manager                = Mockery::spy(\EventManager::class);
        $this->status_semantic_checker      = new MoveStatusSemanticChecker($this->form_element_factory);
        $this->title_semantic_checker       = new MoveTitleSemanticChecker();
        $this->description_semantic_checker = new MoveDescriptionSemanticChecker($this->form_element_factory);
        $this->before_move_artifact         = new BeforeMoveArtifact(
            $this->event_manager,
            $this->title_semantic_checker,
            $this->description_semantic_checker,
            $this->status_semantic_checker
        );

        $this->source_tracker           = Mockery::mock(\Tracker::class);
        $this->source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->source_status_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->source_tracker->shouldReceive('getDescriptionField')->andReturn($this->source_description_field);
        $this->source_tracker->shouldReceive('getStatusField')->andReturn($this->source_status_field);

        $this->target_tracker           = Mockery::mock(\Tracker::class);
        $this->target_status_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->target_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_tracker->shouldReceive('getDescriptionField')->andReturn($this->target_description_field);
        $this->target_tracker->shouldReceive('getStatusField')->andReturn($this->target_status_field);
    }

    public function testSemanticAreAlignedIfBothTrackersHaveAllSemanticsInCommon()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('sb');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => false,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    /**
     * @expectedException \Tuleap\Tracker\Exception\MoveArtifactSemanticsException
     */
    public function testSemanticAreNotAlignedIfBothTrackersHaveOnlyDescriptionSemanticWithFieldsWithDifferentTypes()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('string');

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }

    public function testSemanticAreAlignedIfSourceTrackersHasTitleAndDescriptionSemanticsAndTargetHasOnlyTitleSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => false,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfSourceTrackersHasTitleAndDescriptionSemanticsAndTargetHasOnlyDescriptionSemantic()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => false,
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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => false,
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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => true,
            'hasSemanticsDescription' => true,
            'hasSemanticsStatus'      => true,
        ]);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker));
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyStatusSemanticWithFieldsOfSameType()
    {
        $this->source_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => true,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('sb');

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
            'hasSemanticsStatus'      => true,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => true,
        ]);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('rb');

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
            'hasSemanticsStatus'      => false,
        ]);

        $this->target_tracker->shouldReceive([
            'hasSemanticsTitle'       => false,
            'hasSemanticsDescription' => false,
            'hasSemanticsStatus'      => false,
        ]);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker);
    }
}
