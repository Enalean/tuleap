<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */


class Tracker_FormElement_Field_Integer extends Tracker_FormElement_Field_Numeric {
    
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                return " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_int AS $b ON (
                            $b.changeset_value_id = $a.id
                            AND ". $this->buildMatchExpression("$b.value", $criteria_value) ."
                         ) ";
            }
        }
        return '';
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_int AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
    }
    
    protected function buildMatchExpression($field_name, $criteria_value) {
        return parent::buildMatchExpression($field_name, $criteria_value);
    }
    
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_Int_ValueDao();
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        return $value;
    }
    
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_IntegerDao();
    }
    protected function getDao() {
        return new Tracker_FormElement_Field_IntegerDao();
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','integer');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','integer_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field-int--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $html = '';
        if ($value) {
            $html .= $value->getInteger();
        }
        return $html;
    }
    
    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
    protected function getValidatorErrorMessage() {
        return $this->getLabel() . ' ' . $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_integer_value');
    }
    
    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed) {
        $changeset_value = null;
        if ($row = $this->getValueDao()->searchById($value_id, $this->id)->getRow()) {
            $int_row_value = $row['value'];
            if ($int_row_value !== null) {
                $int_row_value = (int)$int_row_value;
            }
            $changeset_value = new Tracker_Artifact_ChangesetValue_Integer($value_id, $this, $has_changed, $int_row_value);
        }
        return $changeset_value;
    }

}
?>