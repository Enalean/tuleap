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
use Tracker_Semantic_ContributorFactory;
use Tuleap\Tracker\Action\Move\NoFeedbackFieldCollector;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;

require_once __DIR__ . '/../../bootstrap.php';

class BeforeMoveArtifactTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var BeforeMoveArtifact
     */
    private $before_move_artifact;

    protected function setUp(): void
    {
        parent::setUp();

        $this->form_element_factory         = Mockery::mock(Tracker_FormElementFactory::class);
        $this->contributor_semantic_factory = Mockery::mock(Tracker_Semantic_ContributorFactory::class);

        $this->event_manager                = Mockery::spy(\EventManager::class);
        $this->status_semantic_checker      = new MoveStatusSemanticChecker($this->form_element_factory);
        $this->title_semantic_checker       = new MoveTitleSemanticChecker();
        $this->description_semantic_checker = new MoveDescriptionSemanticChecker($this->form_element_factory);
        $this->contributor_semantic_checker = new MoveContributorSemanticChecker($this->form_element_factory);

        $this->feedback_field_collector = new NoFeedbackFieldCollector();

        $this->before_move_artifact = new BeforeMoveArtifact(
            $this->event_manager,
            $this->title_semantic_checker,
            $this->description_semantic_checker,
            $this->status_semantic_checker,
            $this->contributor_semantic_checker
        );

        $this->source_tracker           = Mockery::mock(\Tracker::class);
        $this->source_title_field       = Mockery::mock(Tracker_FormElement_Field::class);
        $this->source_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->source_status_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->source_contributor_field = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->source_external_field    = Mockery::mock(Tracker_FormElement_Field::class);

        $this->source_title_field->shouldReceive('getId')->andReturn(1);
        $this->source_description_field->shouldReceive('getId')->andReturn(2);
        $this->source_status_field->shouldReceive('getId')->andReturn(3);
        $this->source_contributor_field->shouldReceive('getId')->andReturn(4);
        $this->source_external_field->shouldReceive('getId')->andReturn(5);

        $this->source_tracker->shouldReceive('getDescriptionField')->andReturn($this->source_description_field);
        $this->source_tracker->shouldReceive('getStatusField')->andReturn($this->source_status_field);

        $this->target_tracker           = Mockery::mock(\Tracker::class);
        $this->target_status_field      = Mockery::mock(Tracker_FormElement_Field_List::class);
        $this->target_description_field = Mockery::mock(Tracker_FormElement_Field::class);
        $this->target_contributor_field = Mockery::mock(Tracker_FormElement_Field::class);

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('sb');

        $this->form_element_factory->shouldReceive('getType')->with($this->source_contributor_field)->andReturn('msb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_contributor_field)->andReturn('msb');

        $this->assertTrue(
            $this->before_move_artifact->artifactCanBeMoved(
                $this->source_tracker,
                $this->target_tracker,
                $this->feedback_field_collector
            )
        );
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->assertTrue(
            $this->before_move_artifact->artifactCanBeMoved(
                $this->source_tracker,
                $this->target_tracker,
                $this->feedback_field_collector
            )
        );
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('string');

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_description_field)->andReturn('text');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_description_field)->andReturn('text');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn($this->source_title_field);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

    public function testExternalSemanticsAreNotCheckedIfNotReturnedByOtherPlugin()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

    public function testSemanticsAreNotAlignedIfBothTrackerAndExternalSemanticsAreNotAligned()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            $checker = Mockery::mock(MoveSemanticChecker::class);
            $checker->shouldReceive([
                'areBothSemanticsDefined'              => false,
                'doesBothSemanticFieldHaveTheSameType' => false,
                'areSemanticsAligned'                  => false,
                'getSemanticName'                      => 'whatever',
                'getSourceSemanticField'               => $this->source_external_field,
            ]);

            $event->addExternalSemanticsChecker($checker);
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

    public function testSemanticsAreAlignedIfExternalSemanticsAreAlignedAndNotTrackerSemantics()
    {
        $this->event_manager->shouldReceive('processEvent')->with(Mockery::on(function (MoveArtifactGetExternalSemanticCheckers $event) {
            $checker = Mockery::mock(MoveSemanticChecker::class);
            $checker->shouldReceive([
                'areBothSemanticsDefined'              => true,
                'doesBothSemanticFieldHaveTheSameType' => true,
                'areSemanticsAligned'                  => true,
                'getSemanticName'                      => 'whatever',
                'getSourceSemanticField'               => $this->source_external_field,
            ]);

            $event->addExternalSemanticsChecker($checker);
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);
        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('sb');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_status_field)->andReturn('sb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_status_field)->andReturn('rb');

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn(null);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

    public function testSemanticAreAlignedIfBothTrackersHaveOnlyContributorSemanticWithFieldsOfSameType()
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_contributor_field)->andReturn('msb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_contributor_field)->andReturn('msb');

        $this->assertTrue($this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector));
    }

    public function testSemanticAreNotAlignedIfBothTrackersHaveOnlyContributorSemanticWithFieldsWithDifferentTypes()
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn($this->target_contributor_field);

        $this->form_element_factory->shouldReceive('getType')->with($this->source_contributor_field)->andReturn('msb');
        $this->form_element_factory->shouldReceive('getType')->with($this->target_contributor_field)->andReturn('rb');

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }

    public function testSemanticAreNotAlignedIfOneTrackersDoesNotHaveContributorSemantic()
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

        $this->source_tracker->shouldReceive('getTitleField')->andReturn(null);

        $this->source_tracker->shouldReceive('getContributorField')->andReturn($this->source_contributor_field);
        $this->target_tracker->shouldReceive('getContributorField')->andReturn(null);

        $this->expectException(MoveArtifactSemanticsException::class);

        $this->before_move_artifact->artifactCanBeMoved($this->source_tracker, $this->target_tracker, $this->feedback_field_collector);
    }
}
