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

require_once TRACKER_BASE_DIR .'/workflow/PostAction/PostActionSubFactory.class.php';

/**
 * Loads and saves CIBuild post actions
 */
class Transition_PostAction_CIBuildFactory implements Transition_PostActionSubFactory {

    /**
     * @var Array of available post actions classes run after fields validation
     */
    protected $post_actions_classes_ci = array(
        Transition_PostAction_CIBuild::SHORT_NAME => 'Transition_PostAction_CIBuild',
    );

    /** @var Transition_PostAction_CIBuildDao */
    private $dao;

    public function __construct(Transition_PostAction_CIBuildDao $dao) {
        $this->dao = $dao;
    }

    /**
     * @see Transition_PostActionSubFactory::addPostAction()
     */
    public function addPostAction(Transition $transition, $requested_postaction) {
        $job_url = '';
        $this->dao->create($transition->getId(), $job_url);
    }

    /**
     * @see Transition_PostActionSubFactory::loadPostActions()
     */
    public function loadPostActions(Transition $transition) {
        $post_actions = array();

        foreach($this->loadPostActionRows($transition) as $row) {
            $post_actions[] = $this->buildPostAction($transition, $row);
        }

        return $post_actions;
    }

    /**
     * @see Transition_PostActionSubFactory::saveObject()
     */
    public function saveObject(Transition_PostAction $post_action) {
        $this->dao->create($post_action->getTransition()->getId(), $post_action->getJobUrl());
    }

    /**
     * @see Transition_PostActionSubFactory::duplicate()
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping) {
        $this->dao->duplicate($from_transition->getId(), $to_transition_id);
    }

    /**
     * @see Transition_PostActionSubFactory::fetchPostActions()
     */
    public function fetchPostActions() {
        $html = '';
        $html .= '<option value="" selected>--</option>';
        $html .= '<option value="'. Transition_PostAction_CIBuild::SHORT_NAME .'">';
        $html .= Transition_PostAction_CIBuild::getLabel();
        $html .= '</option>';

        return $html;
    }

    /**
     * @see Transition_PostActionSubFactory::deleteWorkflow()
     */
    public function deleteWorkflow($workflow_id) {
        return $this->dao->deletePostActionsByWorkflowId($workflow_id);
    }

    /**
     * @see Transition_PostActionSubFactory::isFieldUsedInPostActions()
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        return false;
    }

    /**
     * @see Transition_PostActionSubFactory::getInstanceFromXML()
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        $postaction_attributes = $xml->attributes();
        $row = array(
            'id'      => 0,
            'job_url' => (string)$postaction_attributes['job_url'],
        );

        return $this->buildPostAction($transition, $row);
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
        $id      = (int)$row['id'];
        $job_url = (string)$row['job_url'];
        $ci_client = new Jenkins_Client(new Http_Client());

        return new Transition_PostAction_CIBuild($transition, $id, $job_url, $ci_client);
    }

    /**
     * Retrieves matching PostAction database records.
     *
     * @param Transition $transition The Transition to which the PostActions must be associated
     * @param string     $shortname  The PostAction type (short name, not class name)
     *
     * @return DataAccessResult
     */
    private function loadPostActionRows(Transition $transition) {
        return $this->dao->searchByTransitionId($transition->getId());
    }
}

?>
