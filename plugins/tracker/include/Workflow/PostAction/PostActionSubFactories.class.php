<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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

/**
 * First class collection of PostActionSubFactories.
 *
 * It is used internally by the PostActionFactory in order to tend toward
 * Open/Closed Principle: just add a subfactory to the collection and all
 * common behaviors (deleteWorkflow, duplicate, ...) are silently aggregated
 * without having to heavily modify the PostActionFactory.
 */
class Transition_PostActionSubFactories
{

    /** @var array of Transition_PostActionSubFactory */
    private $factories;

    /**
     * @param array of Transition_PostActionSubFactory
     */
    public function __construct(array $factories)
    {
        $this->factories = $factories;
    }

    /**
     * Load the post actions that belong to a transition
     *
     * @param Transition $transition The transition
     *
     * @return void
     */
    public function loadPostActions(Transition $transition)
    {
        $post_actions = array();
        foreach ($this->factories as $factory) {
            $post_actions = array_merge($post_actions, $factory->loadPostActions($transition));
        }
        $transition->setPostActions($post_actions);
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field)
    {
        foreach ($this->factories as $factory) {
            if ($factory->isFieldUsedInPostActions($field)) {
                return true;
            }
        }
    }

    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     *
     */
    public function deleteWorkflow($workflow_id)
    {
        $status = true;
        foreach ($this->factories as $factory) {
            $status = $factory->deleteWorkflow($workflow_id) && $status;
        }
        return $status;
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition the template transition
     * @param int $to_transition_id the id of the transition
     * @param Array $field_mapping the field mapping
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        foreach ($this->factories as $factory) {
            $factory->duplicate($from_transition, $to_transition_id, $field_mapping);
        }
    }

    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions()
    {
        $html  = '';
        foreach ($this->factories as $factory) {
            $html .= $factory->fetchPostActions();
        }
        return $html;
    }
}
