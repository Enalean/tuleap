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

use Tracker_Artifact;
use Tracker_FormElement_Container_Fieldset;
use Tracker_FormElementFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\Transition\NoTransitionForStateException;

class HiddenFieldsetsDetector
{
    /**
     * @var TransitionRetriever
     */
    private $transition_retriever;

    /**
     * @var HiddenFieldsetsRetriever
     */
    private $hidden_fieldsets_retriever;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(
        TransitionRetriever $transition_retriever,
        HiddenFieldsetsRetriever $hidden_fieldsets_retriever,
        Tracker_FormElementFactory $form_element_factory
    ) {
        $this->transition_retriever       = $transition_retriever;
        $this->hidden_fieldsets_retriever = $hidden_fieldsets_retriever;
        $this->form_element_factory       = $form_element_factory;
    }

    public function doesArtifactContainHiddenFieldsets(Tracker_Artifact $artifact): bool
    {
        if (! $this->artifactIsEligibleToHiddenFieldsets($artifact)) {
            return false;
        }

        $fieldsets = $this->form_element_factory->getUsedFieldsets($artifact->getTracker());

        foreach ($fieldsets as $fieldset) {
            if ($this->isFieldsetHidden($artifact, $fieldset)) {
                return true;
            }
        }

        return false;
    }

    public function isFieldsetHidden(Tracker_Artifact $artifact, Tracker_FormElement_Container_Fieldset $fieldset): bool
    {
        try {
            $current_state_transition     = $this->transition_retriever->getReferenceTransitionForCurrentState($artifact);
            $hidden_fieldsets_post_action = $this->hidden_fieldsets_retriever->getHiddenFieldsets($current_state_transition);
        } catch (NoTransitionForStateException | NoHiddenFieldsetsPostActionException $e) {
            return false;
        }

        $fieldset_id = (int) $fieldset->getID();

        foreach ($hidden_fieldsets_post_action->getFieldsets() as $fieldset_in_post_action) {
            if ((int) $fieldset_in_post_action->getID() === $fieldset_id) {
                return true;
            }
        }

        return false;
    }

    private function artifactIsEligibleToHiddenFieldsets(Tracker_Artifact $artifact) : bool
    {
        $workflow = $artifact->getWorkflow();

        return $workflow !== null && ! $workflow->isAdvanced();
    }
}
