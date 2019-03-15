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

namespace Tuleap\Tracker\Workflow\PostAction\ReadOnly;

use SimpleXMLElement;
use Tracker_FormElement_Field;
use Transition;
use Transition_PostAction;

class ReadOnlyFieldsFactory implements \Transition_PostActionSubFactory
{
    /** @var ReadOnlyDao */
    private $read_only_dao;

    public function __construct(ReadOnlyDao $read_only_dao)
    {
        $this->read_only_dao = $read_only_dao;
    }

    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions()
    {
        // Not implemented. We do not support the legacy UI for this post action
        return '';
    }

    /**
     * @param Transition $transition
     * @param string     $requested_postaction
     */
    public function addPostAction(Transition $transition, $requested_postaction)
    {
        // Not implemented. We do not support the legacy UI for this post action
    }

    /**
     * Instanciate the post actions of a given transition
     *
     * @param Transition $transition The transition
     *
     * @return Transition_PostAction[]
     */
    public function loadPostActions(Transition $transition) : array
    {
        $rows = $this->read_only_dao->searchByTransitionId((int) $transition->getId());

        $field_ids = [];
        $post_action_id = null;
        foreach ($rows as $row) {
            $field_ids[] = $row['field_id'];
            // There is only one ReadOnlyFields post-action per transition, so we just choose the last row's id
            $post_action_id = $row['postaction_id'];
        }
        if ($post_action_id === null) {
            return [];
        }
        $post_action = new ReadOnlyFields($transition, $post_action_id, $field_ids);
        return [$post_action];
    }

    /**
     * Save a postaction object
     *
     * @param Transition_PostAction $post_action the object to save
     *
     * @return void
     */
    public function saveObject(Transition_PostAction $post_action)
    {
        // Not implemented. We do not support the legacy UI for this new post action
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
        return $this->read_only_dao->isFieldUsedInPostAction($field->getId());
    }

    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     *
     */
    public function deleteWorkflow($workflow_id)
    {
        // This is dead code and should be removed.
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition  the template transition
     * @param int        $to_transition_id the id of the transition
     * @param array      $field_mapping    the field mapping
     *
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping)
    {
        // Not implemented.
    }

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement  $xml        containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition        $transition to which the postaction is attached
     *
     * @return Transition_PostAction The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition)
    {
        // Not implemented.
    }
}
