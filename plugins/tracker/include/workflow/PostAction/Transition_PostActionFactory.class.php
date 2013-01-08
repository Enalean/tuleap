<?php
/**
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once('Field/Transition_PostAction_Field_Date.class.php');
require_once('Field/Transition_PostAction_Field_Int.class.php');
require_once('Field/Transition_PostAction_Field_Float.class.php');
require_once('CIBuild/Transition_PostAction_CIBuild.class.php');
require_once('Field/dao/Transition_PostAction_Field_DateDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_IntDao.class.php');
require_once('Field/dao/Transition_PostAction_Field_FloatDao.class.php');
require_once('CIBuild/Transition_PostAction_CIBuildDao.class.php');
require_once 'Transition_PostAction_NotFoundException.class.php';
require_once 'PostActionSubFactory.class.php';
require_once 'Field/Transition_PostAction_FieldFactory.class.php';
require_once 'CIBuild/Transition_PostAction_CIBuildFactory.class.php';

/**
 * Collection of subfactories to CRUD postactions. Uniq entry point from the transition point of view.
 */
class Transition_PostActionFactory {

    private $shortnames_by_xml_tag_name = array(
        Transition_PostAction_Field_Float::XML_TAG_NAME => Transition_PostAction_Field_Float::SHORT_NAME,
        Transition_PostAction_Field_Int::XML_TAG_NAME   => Transition_PostAction_Field_Int::SHORT_NAME,
        Transition_PostAction_Field_Date::XML_TAG_NAME  => Transition_PostAction_Field_Date::SHORT_NAME,
        Transition_PostAction_CIBuild::XML_TAG_NAME     => Transition_PostAction_CIBuild::SHORT_NAME,
    );

    /** @var Transition_PostAction_FieldFactory */
    private $postaction_field_factory;

    /** @var Transition_PostAction_CIBuildFactory */
    private $postaction_cibuild_factory;

    /** For testing purpose */
    public function setFieldFactory(Transition_PostAction_FieldFactory $postaction_field_factory) {
        $this->postaction_field_factory = $postaction_field_factory;
    }

    /** For testing purpose */
    public function setCIBuildFactory(Transition_PostAction_CIBuildFactory $postaction_cibuild_factory) {
        $this->postaction_cibuild_factory = $postaction_cibuild_factory;
    }

    /** @return Transition_PostAction_FieldFactory */
    private function getFieldFactory() {
        if (!$this->postaction_field_factory) {
            $this->postaction_field_factory = new Transition_PostAction_FieldFactory(
                Tracker_FormElementFactory::instance(),
                new Transition_PostAction_Field_DateDao(),
                new Transition_PostAction_Field_IntDao(),
                new Transition_PostAction_Field_FloatDao()
            );
        }
        return $this->postaction_field_factory;
    }

    /** @return Transition_PostAction_CIBuildFactory */
    private function getCIBuildFactory() {
        if (!$this->postaction_cibuild_factory) {
            $this->postaction_cibuild_factory = new Transition_PostAction_CIBuildFactory(
                new Transition_PostAction_CIBuildDao()
            );
        }
        return $this->postaction_cibuild_factory;
    }

    /** @return Transition_PostActionSubFactory */
    private function getSubFactory($post_action_short_name) {
        $field_factory = $this->getFieldFactory();
        $factories     = array(
            Transition_PostAction_Field_Float::SHORT_NAME => $field_factory,
            Transition_PostAction_Field_Int::SHORT_NAME   => $field_factory,
            Transition_PostAction_Field_Date::SHORT_NAME  => $field_factory,
            Transition_PostAction_CIBuild::SHORT_NAME     => $this->getCIBuildFactory(),
        );
        if (isset($factories[$post_action_short_name])) {
            return $factories[$post_action_short_name];
        }
        throw new Transition_PostAction_NotFoundException('Invalid Post Action type');
    }

    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html  = '';
        $html .= '<p>'.$GLOBALS['Language']->getText('workflow_admin', 'add_new_action');
        $html .= '<select name="add_postaction">';
        $html .= $this->getFieldFactory()->fetchPostActions();
        $html .= $this->getCIBuildFactory()->fetchPostActions();
        $html .= '</select>';
        return $html;
    }

    /**
     * Create a new post action for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    public function addPostAction(Transition $transition, $requested_postaction) {
        $this->getSubFactory($requested_postaction)->addPostAction($transition, $requested_postaction);
    }

    /**
     * Load the post actions that belong to a transition
     *
     * @param Transition $transition The transition
     *
     * @return void
     */
    public function loadPostActions(Transition $transition) {
        $post_actions = array_merge(
            $this->getFieldFactory()->loadPostActions($transition),
            $this->getCIBuildFactory()->loadPostActions($transition)
        );
        $transition->setPostActions($post_actions);
    }

    /**
     * Save a postaction object
     *
     * @param Transition_PostAction $post_action  the object to save
     *
     * @return void
     */
    public function saveObject(Transition_PostAction $post_action) {
        if ($post_action instanceof Transition_PostAction_Field) {
            $this->getFieldFactory()->saveObject($post_action);
        } else {
            $this->getCIBuildFactory()->saveObject($post_action);
        }
    }

    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        return $this->getCIBuildFactory()->isFieldUsedInPostActions($field)
            || $this->getFieldFactory()->isFieldUsedInPostActions($field);
    }

    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     *
     */
    public function deleteWorkflow($workflow_id) {
        return $this->getCIBuildFactory()->deleteWorkflow($workflow_id)
            && $this->getFieldFactory()->deleteWorkflow($workflow_id);
    }

    /**
     * Duplicate postactions of a transition
     *
     * @param Transition $from_transition the template transition
     * @param int $to_transition_id the id of the transition
     * @param Array $field_mapping the field mapping
     */
    public function duplicate(Transition $from_transition, $to_transition_id, array $field_mapping) {
        $this->getCIBuildFactory()->duplicate($from_transition, $to_transition_id, $field_mapping);
        $this->getFieldFactory()->duplicate($from_transition, $to_transition_id, $field_mapping);
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
        $post_actions  = array();
        foreach ($xml->children() as $child) {
            $short_name = $this->deductPostActionShortNameFromXmlTagName($child->getName());
            $subfactory = $this->getSubFactory($short_name);
            $post_actions[] = $subfactory->getInstanceFromXML($child, $xmlMapping, $transition);
        }
        return $post_actions;
    }

    /** @return string */
    private function deductPostActionShortNameFromXmlTagName($xml_tag_name) {
        if (isset($this->shortnames_by_xml_tag_name[$xml_tag_name])) {
            return $this->shortnames_by_xml_tag_name[$xml_tag_name];
        }
    }
}
?>
