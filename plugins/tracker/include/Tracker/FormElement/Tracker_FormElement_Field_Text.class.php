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
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Text.class.php');
require_once(dirname(__FILE__).'/../Report/dao/Tracker_Report_Criteria_Text_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_Value_TextDao.class.php');
require_once('dao/Tracker_FormElement_Field_TextDao.class.php');
require_once('common/include/Codendi_Diff.class.php');
require_once(dirname(__FILE__).'/../Artifact/Tracker_Artifact_ChangesetValue_Text.class.php');

class Tracker_FormElement_Field_Text extends Tracker_FormElement_Field_Alphanum {
    
    public $default_properties = array(
        'rows'      => array(
            'value' => 10,
            'type'  => 'string',
            'size'  => 3,
        ),
        'cols'          => array(
            'value' => 50,
            'type'  => 'string',
            'size'  => 3,
        ),
        'default_value' => array(
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ),
    );

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties, 
     * or specific values of the field.
     * (The field itself will be deleted later)
     * @return boolean true if success
     */
    public function delete() {
        return $this->getDao()->delete($this->id);
    }
    
    public function getCriteriaFrom($criteria) {
        //Only filter query if field is used
        if($this->isUsed()) {
            //Only filter query if criteria is valuated
            if ($criteria_value = $this->getCriteriaValue($criteria)) {
                $a = 'A_'. $this->id;
                $b = 'B_'. $this->id;
                return " INNER JOIN tracker_changeset_value AS $a 
                         ON ($a.changeset_id = c.id AND $a.field_id = $this->id )
                         INNER JOIN tracker_changeset_value_text AS $b
                         ON ($b.changeset_value_id = $a.id
                             AND ". $this->buildMatchExpression("$b.value", $criteria_value) ."
                         ) ";
            }
        }
        return '';
    }
    
    public function getCriteriaWhere($criteria) {
        return '';
    }
    
    public function getQuerySelect() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        return "$R2.value AS `". $this->name ."`";
    }
    
    public function getQueryFrom() {
        $R1 = 'R1_'. $this->id;
        $R2 = 'R2_'. $this->id;
        
        return "LEFT JOIN ( tracker_changeset_value AS $R1 
                    INNER JOIN tracker_changeset_value_text AS $R2 ON ($R2.changeset_value_id = $R1.id)
                ) ON ($R1.changeset_id = c.id AND $R1.field_id = ". $this->id ." )";
    }
    
    protected function buildMatchExpression($field_name, $criteria_value) {
        $matches = array();
        $expr = parent::buildMatchExpression($field_name, $criteria_value);
        if (!$expr) {
            
            // else transform into a series of LIKE %word%
            if (is_array($criteria_value)) {
                $split = preg_split('/\s+/', $criteria_value['value']);
            } else {
                $split = preg_split('/\s+/', $criteria_value);
            }
            $words = array();
            foreach($split as $w) {
                $words[] = $field_name." LIKE ". $this->quote('%'.$w.'%');
            }
            $expr = join(' AND ', $words);
        }
        return $expr;
    }
    
    protected function quote($value) {
        return CodendiDataAccess::instance()->quoteSmart($value);
    }
    protected function getCriteriaDao() {
        return new Tracker_Report_Criteria_Text_ValueDao();
    }
    
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($value, CODENDI_PURIFIER_BASIC, $this->getTracker()->getGroupId());
    }
    
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
        return $value;
    }
    
    protected function getValueDao() {
        return new Tracker_FormElement_Field_Value_TextDao();
    }
    protected function getDao() {
        return new Tracker_FormElement_Field_TextDao();
    }
    
    /**
     * Return true if this field is the semantic title field of the tracker, 
     * false otherwise if not or if there is no title field defined.
     *
     * @return boolean true if the field is the 'title' of the tracker
     */
    protected function isSemanticTitle() {
        $semantic_manager = new Tracker_SemanticManager($this->getTracker());
        $semantics        = $semantic_manager->getSemantics();
        $field            = $semantics['title']->getField();
        return ($field === $this);
    }
    
    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values = array()) {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<textarea name="artifact['. $this->id .']" 
                            rows="'. $this->getProperty('rows') .'" 
                            cols="'. $this->getProperty('cols') .'" 
                            '. ($this->isRequired() ? 'required' : '') .' 
                            >';
        $html .= $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</textarea>';
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
        
        //check if this field is the title we do not allow to change it
        if ($this->isSemanticTitle()) {
            $html .= '<textarea readonly="readonly" title="'.$GLOBALS['Language']->getText('plugin_tracker_artifact_masschange', 'cannot_masschange_title').'">'.$value.'</textarea>';
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= '<textarea name="artifact['. $this->id .']" 
                                rows="'. $this->getProperty('rows') .'" 
                                cols="'. $this->getProperty('cols') .'">';
            $html .= $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML);
            $html .= '</textarea>';
        }
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
            if ($value != null) {
                $value = $value->getText();
            }
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<textarea name="artifact['. $this->id .']" 
                            rows="'. $this->getProperty('rows') .'" 
                            cols="'. $this->getProperty('cols') .'" 
                            '. ($this->isRequired() ? 'required' : '') .' 
                            >';
        $html .= $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</textarea>';
        return $html;
    }

     /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        if ( empty($value) ) {
            return '';
        }
        $output = '';
        switch ($format) {
            case 'html':
                $output = $this->fetchArtifactValueReadOnly($artifact, $value);
                break;
            default:
                $output = $value->getText();
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
        $value = $value ? $value->getText() : '';
        $hp = Codendi_HTMLPurifier::instance();
        return $hp->purify($value, CODENDI_PURIFIER_BASIC, $this->getTracker()->getGroupId());
    }
    
    /**
     * Fetch the changes that has been made to this field in a followup
     * @param Tracker_ $artifact
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     */
    public function fetchFollowUp($artifact, $from, $to) {
        $html = '';
        $html .= 'changed <a href="#show-diff" class="tracker_artifact_showdiff">[diff]</a>';
        $html .= $this->fetchHistory($artifact, $from, $to);
        return $html;
    }
    
    /**
     * Fetch the value to display changes in artifact history
     * @param array $from the value(s) *before*
     * @param array $to   the value(s) *after*
     * @return string
     */
    public function fetchHistory($artifact, $from, $to) {
        $from_value = $this->getValue($from['value_id']);
        $from_value = isset($from_value['value']) ? $from_value['value'] : '';
        $to_value = $this->getValue($to['value_id']);
        $to_value = isset($to_value['value']) ? $to_value['value'] : '';
        
        $callback = array($this, '_filter_html_callback');
        $d = new Codendi_Diff(array_map($callback, explode("\n", $from_value)), 
                              array_map($callback, explode("\n", $to_value)));
        $f = new Codendi_HtmlUnifiedDiffFormatter();
        $diff = $f->format($d);
        return $diff ? $diff : '<em>No changes</em>';
    }
    protected function _filter_html_callback($s) {
        $hp = Codendi_HTMLPurifier::instance();
        return  $hp->purify($s, CODENDI_PURIFIER_CONVERT_HTML);
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
        $html .= '<textarea rows="'. $this->getProperty('rows') .'" cols="'. $this->getProperty('cols') .'" autocomplete="off">';
        $html .=  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) ;
        $html .= '</textarea>';
        return $html;
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','text');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','text_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-spin.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-spin--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     * 
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Text $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($value) {
            $html .= nl2br($hp->purify($value->getText(), CODENDI_PURIFIER_CONVERT_HTML));
        }
        return $html;
    }
    
    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return boolean
     */
    public function takesTwoColumns() {
        return $this->getProperty('cols') > 40;
    }
    
    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracler is ok 
     */
    public function testImport() {
        if(parent::testImport()){
            if (get_class($this) == 'Tracker_FormElement_Field_Text') {
	            if (!(isset($this->default_properties['rows']) && isset($this->default_properties['cols']))) {
	                var_dump($this, 'Properties must be "rows" and "cols"');
	                return false;  
	            }
            } elseif (get_class($this) == 'Tracker_FormElement_Field_String') {
	            if (!(isset($this->default_properties['maxchars']) && isset($this->default_properties['size']))) {
	                var_dump($this, 'Properties must be "maxchars" and "size"');
	                return false;  
	            }
            }
        }
        return true;
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
        $r = $this->getRuleString();
        if (!($is_valid = $r->isValid($value))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_text_value', array($this->getLabel())));
        }
        return $is_valid;
    }
    
    protected function getRuleString() {
        return new Rule_String();
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
            $changeset_value = new Tracker_Artifact_ChangesetValue_Text($value_id, $this, $has_changed, $row['value']);
        }
        return $changeset_value;
    }
    
    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data stored in the db
     * @param mixed                           $new_value               May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges($previous_changesetvalue, $new_value) {
        return $previous_changesetvalue->getText() != $new_value;
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
        parent::saveValue($artifact, $changeset_value_id, $value, $previous_changesetvalue);
        ReferenceManager::instance()->extractCrossRef($value, $artifact->getId(), Tracker_Artifact::REFERENCE_NATURE, $this->getTracker()->getGroupID(), UserManager::instance()->getCurrentUser()->getId(), $this->getTracker()->getItemName());
    }
}
?>
