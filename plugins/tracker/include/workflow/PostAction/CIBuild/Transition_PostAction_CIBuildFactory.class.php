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
 * class Transition_PostAction_CIBuildFactory
 *
 */

class Transition_PostAction_CIBuildFactory {

    /**
     * @var Array of available post actions classes run after fields validation
     */
    protected $post_actions_classes_ci = array(
        Transition_PostAction_CIBuild::SHORT_NAME => 'Transition_PostAction_CIBuild',
    );


    public function getTypes() {
        return array_keys($this->post_actions_classes_ci);
    }

    public function addPostAction(Transition $transition) {
        $this->getDao()->create($transition->getTransitionId());
    }


    public function loadPostActions(Transition $transition) {
        $post_actions = array();

        foreach($this->loadPostActionRows($transition) as $row) {
            $post_actions[] = $this->buildPostAction($transition, $row);
        }

        return $post_actions;
   }

    /**
     * Retrieves matching PostAction database records.
     *
     * @param Transition $transition The Transition to which the PostActions must be associated
     * @param string     $shortname  The PostAction type (short name, not class name)
     *
     * @return DataAccessResult
     */
    public function loadPostActionRows(Transition $transition) {
        return $this->getDao()->searchByTransitionId($transition->getTransitionId());
    }

   /**
    * Save a postaction object
    *
    * @param Transition_PostAction $post_action  the object to save
    * @return void
    */
    public function saveObject(Transition_PostAction $post_action) {
        $this->getDao()->save($post_action->getTransition()->getTransitionId(), $post_action->getJobUrl());
    }

   /**
    * Duplicate postactions of a transition
    *
    * @param int $from_transition_id the id of the template transition
    * @param int $to_transition_id the id of the transition
    * @param Array $postactions
    * @param Array $field_mapping the field mapping
    *
    */
    public function duplicate($from_transition_id, $to_transition_id, $postactions, $field_mapping) {
        foreach ($postactions as $postaction) {
            $from_field_id = $postaction->getFieldId();

            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_field_id) {
                    $to_field_id = $mapping['to'];
                    $this->getDao()->duplicate($from_transition_id, $to_transition_id, $from_field_id, $to_field_id);
                }
            }
        }
    }
    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html = '';
        $html .= '<p>'.$GLOBALS['Language']->getText('workflow_admin', 'add_new_action');
        $html .= '<select name="add_postaction">';
        $html .= '<option value="" selected>--</option>';
        $html .= '<option value="'. Transition_PostAction_CIBuild::SHORT_NAME .'">';
        $html .= Transition_PostAction_CIBuild::getLabel();
        $html .= '</option>';
        $html .= '</select></p>';

        return $html;
    }


    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     *
     */
    public function deleteWorkflow($workflow_id) {
        return $this->getDao()->deletePostActionsByWorkflowId($workflow_id);
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        if ($this->getDao()->countByFieldId($field->getId()) > 0) {
            return true;
        }

        return false;
    }

    /**
     * Creates a postaction Object
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition       $transition     to which the postaction is attached
     *
     * @return Transition_PostAction The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        $xml_tag_name          = $xml->getName();
        $postaction_attributes = $xml->attributes();
        $job_url               = $this->getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes);

        return new Transition_PostAction_CIBuild($transition, 0, $job_url, new Jenkins_Client(new Http_Client()));
    }

   /**
     * Reconstitute a PostAction from database
     *
     * @param Transition $transition The transition to which this PostAction is associated
     * @param mixed      $row        The raw data (array-like)
     * @param string     $shortname  The PostAction short name
     * @param string     $klass      The PostAction class name
     *
     * @return Transition_PostAction
     */
    private function buildPostAction(Transition $transition, $row) {
        $id    = (int) $row['id'];
        $value = (string) $row['job_url'];
        $ci_client = new Jenkins_Client(new Http_Client());

        return new Transition_PostAction_CIBuild($transition, $id, $value, $ci_client);
    }

    /**
     * Returns the corresponding DAO
     *
     * @param string $post_action_short_name
     * @return Transition_PostAction_FieldDao
     */
    private function getDao() {
        return new Transition_PostAction_CIBuildDao();
    }

    private function getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes) {
        switch($xml_tag_name) {
            case 'postaction_ci_build':  return (string) $postaction_attributes['job_url'];
            default: throw new Transition_PostAction_NotFoundException($xml_tag_name);
        }
     }
}

?>
