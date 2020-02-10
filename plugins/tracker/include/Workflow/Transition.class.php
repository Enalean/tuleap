<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 *
 */

use Tuleap\Tracker\Workflow\Transition\OrphanTransitionException;

class Transition // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const EXPORT_XML_FROM_NEW_VALUE = "null";

    public $transition_id;
    public $workflow_id;

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $from = null;

    /**
     * @var Tracker_FormElement_Field_List_Value
     */
    protected $to   = null;

    /**
     * @var Array of Transition_PostAction
     */
    protected $post_actions = array();

    /**
     * @var Array of Transition_PostAction run after fields validation
     */
    protected $post_actions_after = array();

    /**
     * @var Array of Workflow_Transition_Condition
     */
    private $conditions = array();

    /**
     * @var Array of permissions
     */
    protected $cache_permissions = array();

    /**
     * @var Workflow
     */
    protected $workflow = null;

    /**
     * Constructor
     *
     * @param int|null $transition_id Id of the transition
     * @param int $workflow_id Id of the workflow
     * @param Tracker_FormElement_Field_List_Value|null $from          Source value
     * @param Tracker_FormElement_Field_List_Value|null $to            Destination value
     */
    public function __construct($transition_id, $workflow_id, $from, $to)
    {
        $this->transition_id = $transition_id;
        $this->workflow_id   = $workflow_id;
        if ($from !== null) {
            $this->from = $from;
        }
        if ($to !== null) {
            $this->to = $to;
        }
    }

    /**
     * Set workflow
     *
     * @param Workflow $workflow Workflow
     */
    public function setWorkflow(Workflow $workflow)
    {
        $this->workflow = $workflow;
    }

    /**
     * Access From field value
     *
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueFrom()
    {
        return $this->from;
    }

    /**
     * Access To field value
     *
     * @return Tracker_FormElement_Field_List_Value
     */
    public function getFieldValueTo()
    {
        return $this->to;
    }

    /**
     * Says if this transition is equals to another transtion
     *
     * @param Transition $transition The transition to compare
     *
     * @return bool
     */
    public function equals(Transition $transition)
    {
        $source_from = $this->getFieldValueFrom();
        $source_to   = $this->getFieldValueTo();
        $target_from = $transition->getFieldValueFrom();
        $target_to   = $transition->getFieldValueTo();

        return is_null($source_from) && is_null($target_from) && is_null($source_to) && is_null($target_to)

            || is_null($source_from) && is_null($target_from) && !is_null($source_to) && !is_null($target_to)
                && $source_to->getId() === $target_to->getId()

            || !is_null($source_from) && !is_null($target_from) && is_null($source_to) && is_null($target_to)
                && $source_from->getId() === $target_from->getId()

            || !is_null($source_from) && !is_null($target_from) && !is_null($source_to) && !is_null($target_to)
                && $source_from->getId() === $target_from->getId() && $source_to->getId() === $target_to->getId();
    }

    /**
     * Get the transition id
     *
     * @deprecated since Tuleap 5.7
     * @see getId()
     *
     * @return int
     */
    public function getTransitionId()
    {
        return $this->transition_id;
    }

    public function getId()
    {
        return $this->transition_id;
    }

    /**
     * Set the transition id
     *
     * @param int $id the transition id
     *
     * @return int
     */
    public function setTransitionId($id)
    {
        $this->transition_id = $id;
    }

    /**
     * Get parent workflow
     *
     * @return Workflow
     * @throws OrphanTransitionException if this transition has no workflow
     */
    public function getWorkflow()
    {
        if (!$this->workflow) {
            $this->workflow = WorkflowFactory::instance()->getWorkflow($this->workflow_id);
        }
        if ($this->workflow === null) {
            throw new OrphanTransitionException($this);
        }
        return $this->workflow;
    }

    public function displayTransitionDetails()
    {
    }

    /**
     * @return int
     */
    public function getGroupId()
    {
        return $this->getWorkflow()->getTracker()->getGroupId();
    }

    /**
     * Execute actions before transition happens
     *
     * @param array $fields_data Request field data (array[field_id] => data)
     * @param PFUser  $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(&$fields_data, PFUser $current_user)
    {
        $post_actions = $this->getPostActions();
        foreach ($post_actions as $post_action) {
            $post_action->before($fields_data, $current_user);
        }
    }

    /**
     * Execute actions after transition happenstype
     *
     * @return void
     */
    public function after(Tracker_Artifact_Changeset $changeset)
    {
        $post_actions = $this->getPostActions();
        foreach ($post_actions as $post_action) {
            $post_action->after($changeset);
        }
    }

    /**
     * Validate that transition can occur
     *
     * @param array $fields_data Request field data (array[field_id] => data)
     *
     * @return bool true if the transition can occur, false otherwise
     */
    public function validate($fields_data, Tracker_Artifact $artifact, $comment_body)
    {
        return $this->getConditions()->validate($fields_data, $artifact, $comment_body);
    }

    /**
     * Set Post Actions for the transition
     *
     * @param array $post_actions array of Transition_PostAction
     *
     * @return void
     */
    public function setPostActions($post_actions)
    {
        $this->post_actions = $post_actions;
    }

    /**
     * Get Post Actions for the transition
     *
     * @return array
     */
    public function getPostActions()
    {
        return $this->post_actions;
    }

    /**
     * Get Post Actions for the transition
     *
     * @return Transition_PostAction[]
     */
    public function getAllPostActions()
    {
        return array_merge($this->post_actions, $this->post_actions_after);
    }

    /**
     * Get Post Actions for the transition
     *
     * @return array
     */
    public function getPostActionsAfter()
    {
        return $this->post_actions_after;
    }

    /**
     * @deprecated use ConditionFactory get*Condition methods
     * @return Workflow_Transition_ConditionsCollection
     */
    public function getConditions()
    {
        if (! $this->conditions) {
            $this->conditions = $this->getConditionFactory()->getConditions($this);
        }
        return $this->conditions;
    }

    public function setConditions(Workflow_Transition_ConditionsCollection $conditions)
    {
        $this->conditions = $conditions;
    }

    /**
     * @return Workflow_Transition_ConditionFactory
     */
    private function getConditionFactory()
    {
        return Workflow_Transition_ConditionFactory::build();
    }

    /**
     * Export transition to XML
     *
     * @param SimpleXMLElement &$root     the node to which the transition is attached (passed by reference)
     * @param array            $xmlMapping correspondance between real ids and xml IDs
     *
     * @return void
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        $child = $root->addChild('transition');
        if ($this->getFieldValueFrom() == null) {
            $child->addChild('from_id')->addAttribute('REF', self::EXPORT_XML_FROM_NEW_VALUE);
        } else {
            $child->addChild('from_id')->addAttribute('REF', array_search($this->getFieldValueFrom()->getId(), $xmlMapping['values']));
        }
        $child->addChild('to_id')->addAttribute('REF', array_search($this->getFieldValueTo()->getId(), $xmlMapping['values']));

        $postactions = $this->getPostActions();
        if ($postactions) {
            $grand_child = $child->addChild('postactions');
            foreach ($postactions as $postaction) {
                $postaction->exportToXML($grand_child, $xmlMapping);
            }
        }

        $this->getConditions()->exportToXML($child, $xmlMapping);
    }

   /**
    * Indicates if permissions on a field can be bypassed
    *
    *
    * @return bool true if the permissions on the field can be by passed, false otherwise
    */
    public function bypassPermissions(Tracker_FormElement_Field $field)
    {
        $postactions = $this->getPostActions();
        foreach ($postactions as $postaction) {
            if ($postaction->bypassPermissions($field)) {
                return true;
            }
        }
        return false;
    }

    public function getIdFrom()
    {
        $from = $this->getFieldValueFrom();
        if ($from) {
            return $from->getId();
        }
        return '';
    }

    public function getIdTo()
    {
        $to = $this->getFieldValueTo();
        if ($to) {
            return $to->getId();
        }
        return '';
    }
}
