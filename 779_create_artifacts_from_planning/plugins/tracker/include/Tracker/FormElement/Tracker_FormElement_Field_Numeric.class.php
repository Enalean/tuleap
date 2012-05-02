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

require_once('Tracker_FormElement_Field_Alphanum.class.php');
abstract class Tracker_FormElement_Field_Numeric extends Tracker_FormElement_Field_Alphanum {
    
    public $default_properties = array(
        'maxchars'      => array(
            'value' => 0,
            'type'  => 'string',
            'size'  => 3,
        ),
        'size'          => array(
            'value' => 5,
            'type'  => 'string',
            'size'  => 3,
        ),
        'default_value' => array(
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ),
    );
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.value AS `". $this->name ."`";
    }
    
    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions) {
        $R1  = 'R1_'. $this->id;
        $R2  = 'R2_'. $this->id;
        $same     = array();
        $separate = array();
        foreach ($functions as $f) {
            if (in_array($f, $this->getAggregateFunctions())) {
                if (substr($f, -5) === '_GRBY') {
                    $separate[] = array(
                        'function' => $f,
                        'select'   => "$R2.value AS label, count(*) AS value",
                        'group_by' => "$R2.value",
                    );
                } else {
                    $same[] = "$f($R2.value) AS `". $this->name ."_$f`";
                }
            }
        }
        return array(
            'same_query'       => implode(', ', $same),
            'separate_queries' => $separate,
        );
    }
    
    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions() {
        return array('AVG', 'COUNT', 'COUNT_GRBY', 'MAX', 'MIN', 'STD', 'SUM');
    }
    
    protected function buildMatchExpression($field_name, $criteria_value) {
        $expr = parent::buildMatchExpression($field_name, $criteria_value);
        if (!$expr) {
            $matches = array();
            if (preg_match("/^(<|>|>=|<=)\s*($this->pattern)$/", $criteria_value, $matches)) {
                // It's < or >,  = and a number then use as is
                $matches[2] = (string)($this->cast($matches[2]));
                $expr = $field_name.' '.$matches[1].' '.$matches[2];
                
            } else if (preg_match("/^($this->pattern)$/", $criteria_value, $matches)) {
                // It's a number so use  equality
                $matches[1] = $this->cast($matches[1]);
                $expr = $field_name.' = '.$matches[1];
                
            } else if (preg_match("/^($this->pattern)\s*-\s*($this->pattern)$/", $criteria_value, $matches)) {
                // it's a range number1-number2
                $matches[1] = (string)($this->cast($matches[1]));
                $matches[2] = (string)($this->cast($matches[2]));
                $expr = $field_name.' >= '.$matches[1].' AND '.$field_name.' <= '. $matches[2];
                
            } else {
                // Invalid syntax - no condition
                $expr = '1';
            }
        }
        return $expr;
    }
    
    protected $pattern = '[+\-]*[0-9]+';
    protected function cast($value) {
        return (int)$value;
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $html = '';
        $value = '';
        if (!empty($submitted_values)) {            
            $value=$submitted_values[$this->getId()];
        }else if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" 
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         name="artifact['. $this->id .']" 
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        $html = '';
        $value = $GLOBALS['Language']->getText('global','unchanged');
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text"
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         name="artifact['. $this->id .']"
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $html = '';
        if (is_array($submitted_values[0])) {
            $value=$submitted_values[0][$this->getId()];
        } else {
            if ($value !=null) {
                $value = $value->getValue();
            }
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" 
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         name="artifact['. $this->id .']" 
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
        return $html;
    }

    /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           mail format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        if ( empty($value) ) {
            return '';
        }
        $output = '';
        switch($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $value  = $value->getNumeric();
                $output = $value;
                break;
        }        
        return $output;
    }


    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        if ( empty($value) ) {
            return '';
        }
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify( "{$value->getValue()}", CODENDI_PURIFIER_CONVERT_HTML);
    }
    
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param mixed                           $new_value May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Tracker_Artifact_ChangesetValue $old_value, $new_value) {
       return $old_value->getNumeric() != $new_value;
    }
    
    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $value = '';
        if ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        $html .= '<input type="text" 
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" autocomplete="off" />';
        return $html;
    }
    
    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to) {
        $html = '';
        if (!$from || !($from_value = $from->getNumeric())) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact','set_to').' ';
        } else {
            $html .= ' '.$GLOBALS['Language']->getText('plugin_tracker_artifact','changed_from').' '. $from_value .'  '.$GLOBALS['Language']->getText('plugin_tracker_artifact','to').' ';
        }
        $html .= $to->getNumeric();
        return $html;
    }
    
    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact 
     * @param mixed            $value    data coming from the request. May be string or array. 
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        $is_valid = true;
        if ($value) {
            if (!($is_valid = preg_match('/^'. $this->pattern .'$/', $value))) {
                $GLOBALS['Response']->addFeedback('error', $this->getValidatorErrorMessage());
            }
        }
        return $is_valid;
    }
    
    /**
     * @return string the i18n error message to display if the value submitted by the user is not valid
     */
    protected abstract function getValidatorErrorMessage();
    
    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracler is ok 
     */
    public function testImport() {
        if(parent::testImport()){
            if (!($this->default_properties['maxchars'] && $this->default_properties['size'])) {
                var_dump($this, 'Properties must be "maxchars" and "size"');
                return false;  
            }
        }
        return true;
    }
}
?>