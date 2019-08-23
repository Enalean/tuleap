<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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

use Tuleap\Tracker\Workflow\Transition\Condition\Visitor;

class Workflow_Transition_Condition_FieldNotEmpty extends Workflow_Transition_Condition
{ //phpcs:ignore

    /** @var string */
    public $identifier = 'notempty';

    /** @var Tracker_FormElement_Field */
    private $fields = array();

    /** @var Workflow_Transition_Condition_FieldNotEmpty_Dao */
    private $dao;

    public function __construct(Transition $transition, Workflow_Transition_Condition_FieldNotEmpty_Dao $dao, ?Tracker_Artifact $artifact = null)
    {
        parent::__construct($transition);
        $this->dao                = $dao;
        $this->formElementFactory = Tracker_FormElementFactory::instance();
    }

    /**
     * @see Workflow_Transition_Condition::fetch()
     * @return string The field wrapped in Html
     */
    public function fetch()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $html    .= $GLOBALS['Language']->getText('workflow_admin', 'label_define_transition_required_field');
        $html    .= '<br />';
        $html    .= $GLOBALS['Language']->getText('workflow_admin', 'the_field') . ' ';
        $html    .= '<select multiple name="add_notempty_condition[]">';

        $selected = '';
        if (empty($this->fields)) {
            $selected = 'selected="selected"';
        }
        $html .= '<option value="0" '. $selected .'>';
        $html .= $GLOBALS['Language']->getText('global', 'please_choose_dashed');
        $html .= '</option>';

        foreach ($this->getSelectableFields() as $field) {
            $selected = '';
            if (in_array($field, $this->fields)) {
                $selected .= 'selected="selected"';
            }

            $html .= '<option value="' . $purifier->purify($field->getId()) . '" '. $selected .'>';
            $html .= $purifier->purify($field->getLabel());
            $html .= '</option>';
        }
        $html .= '</select>';
        $html .= ' ' . $GLOBALS['Language']->getText('workflow_admin', 'field_not_empty');

        return $html;
    }

    /**
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if (! $this->fields) {
            return null;
        }

        $child = $root->addChild('condition');
        $child->addAttribute('type', $this->identifier);
        foreach ($this->fields as $field) {
            $grand_child = $child->addChild('field');
            $grand_child->addAttribute('REF', array_search($field->getId(), $xmlMapping));
        }
    }

    /**
     * @see Workflow_Transition_Condition::saveObject()
     */
    public function saveObject()
    {
        $this->dao->create($this->getTransition()->getId(), $this->getFieldIds());
    }

    public function addField(Tracker_FormElement_Field $field)
    {
        $this->fields[] = $field;
    }

    public function getFieldIds()
    {
        $ids = array();
        foreach ($this->fields as $field) {
            $ids[] = $field->getId();
        }

        return $ids;
    }

    /**
     * Get all non dynamic fields where the condition may occur
     *
     * @return array Array of Tracker_FormElement_Field
     */
    private function getSelectableFields()
    {
        $tracker = $this->transition->getWorkflow()->getTracker();
        return $this->formElementFactory->getUsedNonDynamicFields($tracker);
    }

    /**
     *
     * @return bool
     */
    public function validate($fields_data, Tracker_Artifact $artifact, $comment_body)
    {
        if (empty($this->fields)) {
            return true;
        }

        $is_valid = true;
        foreach ($this->fields as $field) {
            $value = $this->getFieldValue($fields_data, $artifact, $field);

            if ($field->isEmpty($value, $artifact)) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_condition', 'invalid_condition', $field->getLabel(). ' ('. $field->getName() .')'));
                $field->setHasErrors(true);
                $is_valid = false;
            }
        }

        return $is_valid;
    }

    private function getFieldValue($fields_data, Tracker_Artifact $artifact, Tracker_FormElement_Field $field)
    {
        $field_id = $field->getId();
        if (isset($fields_data[$field_id])) {
            return $fields_data[$field_id];
        }
        return $this->getFieldValueFromLastChangeset($artifact, $field);
    }

    private function getFieldValueFromLastChangeset(Tracker_Artifact $artifact, Tracker_FormElement_Field $field)
    {
        $value = null;
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset) {
            $last_changeset_value = $last_changeset->getValue($field);
            if ($last_changeset_value) {
                $value = $last_changeset_value->getValue();
            }
        }
        return $value;
    }

    public function accept(Visitor $visitor)
    {
        $visitor->visitFieldNotEmpty($this);
    }
}
