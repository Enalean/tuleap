<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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
 * Interface to define the factory for a dedicated type of PostAction.
 */

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
interface Transition_PostActionSubFactory
{
    /**
     * Prepare what needs to be prepared to efficiently fetch data from the DB in case of workflow load
     */
    public function warmUpCacheForWorkflow(Workflow $workflow): void;

    /**
     * Instantiate the post actions of a given transition
     *
     * @return Transition_PostAction[]
     */
    public function loadPostActions(Transition $transition): array;

    /**
     * Save a postaction object
     *
     * @param Transition_PostAction $post_action  the object to save
     *
     * @return void
     */
    public function saveObject(Transition_PostAction $post_action);

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field);

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition the template transition
     * @param int $to_transition_id the id of the transition
     * @param Array $field_mapping the field mapping
     *
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping);

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition       $transition     to which the postaction is attached
     *
     * @return Transition_PostAction|null The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition);
}
