<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Workflow_Transition_Condition_FieldNotEmpty extends Workflow_Transition_Condition
{
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
     * @see Workflow_Transition_Condition::exportToXml()
     */
    public function exportToXml(SimpleXMLElement $root, $xmlMapping)
    {
        if (! $this->fields) {
            return;
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
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('workflow_condition', 'invalid_condition', $field->getLabel() . ' (' . $field->getName() . ')'));
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
