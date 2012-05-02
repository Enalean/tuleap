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

require_once('Tracker_FormElement_Field.class.php');

/**
 * Base class for alphanumeric fields (Int, Float, String, Text)
 */
abstract class Tracker_FormElement_Field_Alphanum extends Tracker_FormElement_Field {
    
    protected function buildMatchExpression($field_name, $criteria_value) {
        $expr = '';
        $matches = array();
        // If it is sourrounded by /.../ then assume a regexp
        if (preg_match('#(!?)/(.*)/#', $criteria_value, $matches)) {
            
            //if it has a ! at the beginning then assume negation
            // !/toto/ => will search all content that doesn't contain the word 'toto'
            $not = '';
            if ($matches[1]) {
                $not = ' NOT';
            }
            $expr = $field_name . $not ." RLIKE ". $this->quote($matches[2]);
        }
        return $expr;
    }
    
    protected function quote($string) {
        return $this->getCriteriaDao()->da->quoteSmart($string);
    }
    
    public function fetchCriteriaValue($criteria) {        
        $html = '<input type="text" name="criteria['. $this->id .']" id="tracker_report_criteria_'. $this->id .'" value="';
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= $hp->purify($criteria_value, CODENDI_PURIFIER_CONVERT_HTML);
        }
        $html .= '" />';
        return $html;
    }
    
    public function fetchAdvancedCriteriaValue($criteria) {
        return null;
    }    
    
    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.value";
    }
    
    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value) {
        return $value;
    }
    
    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset) {
        $value = '';
        if ($v = $changeset->getValue($this)) {
            if ($row = $this->getValueDao()->searchById($v->getId(), $this->id)->getRow()) {
                $value = $row['value'];
            }
        }
        return $value;
    }
    
    /**
     * Save the value and return the id
     * 
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value 
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
        return $this->getValueDao()->create($changeset_value_id, $value);
    }
    
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
     public function getSoapAvailableValues() {
         return null;
     }
     
}
?>