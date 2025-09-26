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

use PHPUnit\Framework\MockObject\MockObject;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsDetectorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private TransitionRetriever&MockObject $transition_retriever;

    private HiddenFieldsetsDetector $hidden_fieldsets_detector;

    private HiddenFieldsetsRetriever&MockObject $hidden_retriever;

    private Tracker_FormElementFactory&MockObject $form_element_factory;

    #[\Override]
    protected function setUp(): void
    {
        $this->transition_retriever      = $this->createMock(TransitionRetriever::class);
        $this->hidden_retriever          = $this->createMock(HiddenFieldsetsRetriever::class);
        $this->form_element_factory      = $this->createMock(Tracker_FormElementFactory::class);
        $this->hidden_fieldsets_detector = new HiddenFieldsetsDetector(
            $this->transition_retriever,
            $this->hidden_retriever,
            $this->form_element_factory
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenNoTransitionIsDefinedForCurrentState(): void
    {
        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willThrowException(new NoTransitionForStateException());

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden(
                $this->createMock(Artifact::class),
                $this->createMock(FieldsetContainer::class)
            )
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenNoHiddenFieldsetsPostAction(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $fieldset = $this->createMock(FieldsetContainer::class);

        $transition = $this->createMock(\Transition::class);
        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);

        $this->hidden_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willThrowException(new NoHiddenFieldsetsPostActionException());

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testIsFieldsetHiddenReturnsFalseWhenGivenFieldsetIsNotAmongHiddenFieldsets(): void
    {
        $fieldset                     = $this->createMock(FieldsetContainer::class);
        $artifact                     = $this->createMock(Artifact::class);
        $transition                   = $this->createMock(\Transition::class);
        $hidden_fieldsets_post_action = $this->createMock(HiddenFieldsets::class);
        $fieldset_hidden              = $this->createMock(FieldsetContainer::class);

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);

        $this->hidden_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willReturn($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->method('getFieldsets')
            ->willReturn([$fieldset_hidden]);

        $fieldset->method('getID')->willReturn('312');
        $fieldset_hidden->method('getID')->willReturn('999');

        $this->assertFalse(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testIsFieldFrozenReturnsTrueWhenGivenFieldIsReadOnly(): void
    {
        $fieldset                     = $this->createMock(FieldsetContainer::class);
        $artifact                     = $this->createMock(Artifact::class);
        $transition                   = $this->createMock(\Transition::class);
        $hidden_fieldsets_post_action = $this->createMock(HiddenFieldsets::class);
        $fieldset_hidden              = $this->createMock(FieldsetContainer::class);

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);

        $this->hidden_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willReturn($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->method('getFieldsets')
            ->willReturn([$fieldset_hidden]);

        $fieldset->method('getID')->willReturn('312');
        $fieldset_hidden->method('getID')->willReturn('312');

        $this->assertTrue(
            $this->hidden_fieldsets_detector->isFieldsetHidden($artifact, $fieldset)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnTrueIfThereAny(): void
    {
        $fieldset                     = $this->createMock(FieldsetContainer::class);
        $artifact                     = $this->createMock(Artifact::class);
        $transition                   = $this->createMock(\Transition::class);
        $hidden_fieldsets_post_action = $this->createMock(HiddenFieldsets::class);
        $fieldset_hidden              = $this->createMock(FieldsetContainer::class);
        $tracker                      = $this->createMock(Tracker::class);
        $workflow                     = $this->createMock(\Workflow::class);

        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->form_element_factory->method('getUsedFieldsets')->willReturn([$fieldset]);

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);

        $this->hidden_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willReturn($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->method('getFieldsets')
            ->willReturn([$fieldset_hidden]);

        $fieldset->method('getID')->willReturn('312');
        $fieldset_hidden->method('getID')->willReturn('312');

        $this->assertTrue(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnFalseIfThereAreNoFieldsets(): void
    {
        $artifact = $this->createMock(Artifact::class);
        $tracker  = $this->createMock(Tracker::class);
        $workflow = $this->createMock(\Workflow::class);

        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->form_element_factory->method('getUsedFieldsets')->willReturn([]);

        $this->assertFalse(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }

    public function testDoesArtifactContainsHiddenFieldsetsShouldReturnFalseIfThereAreNoHiddenFieldsets(): void
    {
        $fieldset                     = $this->createMock(FieldsetContainer::class);
        $artifact                     = $this->createMock(Artifact::class);
        $transition                   = $this->createMock(\Transition::class);
        $hidden_fieldsets_post_action = $this->createMock(HiddenFieldsets::class);
        $tracker                      = $this->createMock(Tracker::class);
        $workflow                     = $this->createMock(\Workflow::class);

        $artifact->method('getTracker')->willReturn($tracker);
        $artifact->method('getWorkflow')->willReturn($workflow);
        $workflow->method('isAdvanced')->willReturn(false);

        $this->form_element_factory->method('getUsedFieldsets')->willReturn([$fieldset]);

        $this->transition_retriever->method('getReferenceTransitionForCurrentState')
            ->willReturn($transition);

        $this->hidden_retriever
            ->method('getHiddenFieldsets')
            ->with($transition)
            ->willReturn($hidden_fieldsets_post_action);

        $hidden_fieldsets_post_action
            ->method('getFieldsets')
            ->willReturn([]);

        $fieldset->method('getID')->willReturn('312');

        $this->assertFalse(
            $this->hidden_fieldsets_detector->doesArtifactContainHiddenFieldsets($artifact)
        );
    }
}
