<?php
/**
 * Copyright (c) Enalean, 2013 - Present. All Rights Reserved.
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

use Tuleap\Tracker\Artifact\Artifact;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_Workflow_Trigger_FieldValue
{
    private $field;
    private $value;

    public function __construct(
        Tracker_FormElement_Field_List $field,
        Tracker_FormElement_Field_List_BindValue $value,
    ) {
        $this->field = $field;
        $this->value = $value;
    }

    /**
     * @return Tracker_FormElement_Field_List
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @return Tracker_FormElement_Field_List_BindValue
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return Array
     */
    public function fetchFormattedForJson()
    {
        return [
            'tracker_name' => $this->getField()->getTracker()->getName(),
            'field_id' => $this->getField()->getId(),
            'field_label' => $this->getField()->getLabel(),
            'field_value_id' => $this->getValue()->getId(),
            'field_value_label' => $this->getValue()->getLabel(),
        ];
    }

    /**
     * Returns the value formated as needed for changeset creation/update
     *
     * @return Array
     */
    public function getFieldData()
    {
        return [
            $this->getField()->getId() => $this->getValue()->getId(),
        ];
    }

    /**
     * Return true if given artifact has the same value than current object
     *
     * @return bool
     */
    public function isSetForArtifact(Artifact $artifact)
    {
        $artifact_value = $artifact->getValue($this->getField());
        if ($artifact_value && $artifact_value->getValue() == [$this->getValue()->getId()]) {
            return true;
        }
        return false;
    }

    /**
     * Format the rule to be presented to user as a followup comment
     *
     * @param String $condition
     *
     * @return String
     */
    public function getAsChangesetComment($condition)
    {
        $tracker = $this->getField()->getTracker();
        assert($tracker instanceof \Tracker);
        if ($condition === 'all_of') {
            return sprintf(dgettext('tuleap-tracker', 'all of <strong>%1$s %2$s</strong> are set to <strong>%3$s</strong>'), $tracker->getName(), $this->getField()->getLabel(), $this->getValue()->getLabel());
        }

        return sprintf(dgettext('tuleap-tracker', 'at least one <strong>%1$s %2$s</strong> equals <strong>%3$s</strong>'), $tracker->getName(), $this->getField()->getLabel(), $this->getValue()->getLabel());
    }
}
