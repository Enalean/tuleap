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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tracker;
use Tracker_Artifact;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

final class HiddenFieldsetsDetectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\MockInterface|TransitionRetriever
     */
    private $transition_retriever;

    /** @var HiddenFieldsetsDetector*/
    private $hidden_fieldsets_detector;

    /** @var Mockery\MockInterface */
    private $hidden_retriever;

    /** @var Mockery\MockInterface */
    private $form_element_factory;

    protected function setUp(): void
    {
        $this->transition_retriever      = Mockery::mock(TransitionRetriever::class);
        $this->hidden_retriever          = Mockery::mock(HiddenFieldsetsRetriever::class);
        $this->form_element_factory      = Mockery::mock(Tracker_FormElementFactory::class);
        $this->hidden_fieldsets_detector = new HiddenFieldsetsDetector(
            $this->transition_retriever,
            $this->hidden_retriever,
            $this->form_element_factory
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenNoTransitionIsDefinedForCurrentState(): void
    {
        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andThrow(NoTransitionForStateException::class);

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden(
                Mockery::mock(Tracker_Artifact::class),
                Mockery::mock(Tracker_FormElement_Container_Fieldset::class)
            )
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenNoHiddenFieldsetsPostAction(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $fieldset = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $transition = Mockery::mock(\Transition::class);
        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);

        $this->hidden_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andThrows(new NoHiddenFieldsetsPostActionException());

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenGivenFieldsetIsNotAmongHiddenFieldsets(): void
    {
        $fieldset                     = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $artifact                     = Mockery::mock(Tracker_Artifact::class);
        $transition                   = Mockery::mock(\Transition::class);
        $hidden_fieldsets_post_action = Mockery::mock(HiddenFieldsets::class);
        $fieldset_hidden              = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);

        $this->hidden_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andReturns($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->shouldReceive('getFieldsets')
            ->andReturns([$fieldset_hidden]);

        $fieldset->shouldReceive('getID')->andReturns('312');
        $fieldset_hidden->shouldReceive('getID')->andReturns('999');

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testIsFieldFrozenReturnsTrueWhenGivenFieldIsReadOnly(): void
    {
        $fieldset                     = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $artifact                     = Mockery::mock(Tracker_Artifact::class);
        $transition                   = Mockery::mock(\Transition::class);
        $hidden_fieldsets_post_action = Mockery::mock(HiddenFieldsets::class);
        $fieldset_hidden              = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);

        $this->hidden_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andReturns($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->shouldReceive('getFieldsets')
            ->andReturns([$fieldset_hidden]);

        $fieldset->shouldReceive('getID')->andReturns('312');
        $fieldset_hidden->shouldReceive('getID')->andReturns('312');

        $this->assertTrue(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnTrueIfThereAny(): void
    {
        $fieldset                     = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $artifact                     = Mockery::mock(Tracker_Artifact::class);
        $transition                   = Mockery::mock(\Transition::class);
        $hidden_fieldsets_post_action = Mockery::mock(HiddenFieldsets::class);
        $fieldset_hidden              = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $tracker                      = Mockery::mock(Tracker::class);
        $workflow                     = Mockery::mock(\Workflow::class);

        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('isAdvanced')->andReturnFalse();

        $this->form_element_factory->shouldReceive('getUsedFieldsets')->andReturn([$fieldset]);

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);

        $this->hidden_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andReturns($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->shouldReceive('getFieldsets')
            ->andReturns([$fieldset_hidden]);

        $fieldset->shouldReceive('getID')->andReturns('312');
        $fieldset_hidden->shouldReceive('getID')->andReturns('312');

        $this->assertTrue(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnFalseIfThereAreNoFieldsets(): void
    {
        $artifact = Mockery::mock(Tracker_Artifact::class);
        $tracker  = Mockery::mock(Tracker::class);
        $workflow = Mockery::mock(\Workflow::class);

        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('isAdvanced')->andReturnFalse();

        $this->form_element_factory->shouldReceive('getUsedFieldsets')->andReturn([]);

        $this->assertFalse(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnFalseIfThereAreNoHiddenFieldsets(): void
    {
        $fieldset                     = Mockery::mock(Tracker_FormElement_Container_Fieldset::class);
        $artifact                     = Mockery::mock(Tracker_Artifact::class);
        $transition                   = Mockery::mock(\Transition::class);
        $hidden_fieldsets_post_action = Mockery::mock(HiddenFieldsets::class);
        $tracker                      = Mockery::mock(Tracker::class);
        $workflow                     = Mockery::mock(\Workflow::class);

        $artifact->shouldReceive('getTracker')->andReturn($tracker);
        $artifact->shouldReceive('getWorkflow')->andReturn($workflow);
        $workflow->shouldReceive('isAdvanced')->andReturnFalse();

        $this->form_element_factory->shouldReceive('getUsedFieldsets')->andReturn([$fieldset]);

        $this->transition_retriever->shouldReceive('getReferenceTransitionForCurrentState')
            ->andReturns($transition);

        $this->hidden_retriever
            ->shouldReceive('getHiddenFieldsets')
            ->with($transition)
            ->andReturns($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->shouldReceive('getFieldsets')
            ->andReturns([]);

        $fieldset->shouldReceive('getID')->andReturns('312');

        $this->assertFalse(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }
}
