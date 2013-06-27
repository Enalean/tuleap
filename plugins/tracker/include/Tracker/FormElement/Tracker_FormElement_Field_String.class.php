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


class Tracker_FormElement_Field_String extends Tracker_FormElement_Field_Text {
    
    public $default_properties = array(
        'maxchars'      => array(
            'value' => 0,
            'type'  => 'string',
            'size'  => 3,
        ),
        'size'          => array(
            'value' => 30,
            'type'  => 'string',
            'size'  => 3,
        ),
        'default_value' => array(
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ),
    );

    protected function getDao() {
        return new Tracker_FormElement_Field_StringDao();
    }
  
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

    /**
     * Fetch the html code to display the field value in new artifact submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValue($submitted_values=array()) {
        $html  = '';
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $hp    = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" 
                         name="artifact['. $this->id .']"  
                         '. ($this->isRequired() ? 'required' : '') .' 
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
        return $html;
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange() {
        $html = '';
        $value = $GLOBALS['Language']->getText('global','unchanged');

        if ($this->isSemanticTitle()) {
            $html .= '<input type="text" readonly="readonly" value="'.$value.'" title="'.$GLOBALS['Language']->getText('plugin_tracker_artifact_masschange', 'cannot_masschange_title').'" />';
        } else {
            $hp = Codendi_HTMLPurifier::instance();
            $html .= '<input type="text"
                             name="artifact['. $this->id .']"
                             size="'. $this->getProperty('size') .'"
                             '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                             value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
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
            if($value != null) {
                $value = $value->getText();
            }
        }
        $hp = Codendi_HTMLPurifier::instance();
        $html .= '<input type="text" 
                         name="artifact['. $this->id .']"  
                         '. ($this->isRequired() ? 'required' : '') .' 
                         size="'. $this->getProperty('size') .'"
                         '. ($this->getProperty('maxchars') ? 'maxlength="'. $this->getProperty('maxchars') .'"' : '')  .'
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML)  .'" />';
        return $html;
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
                         value="'.  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) .'" autocomplete="off" />';
        return $html;
    }
    
    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','string');
    }
    
    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','string_description');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field.png');
    }
    
    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-text-field--plus.png');
    }
    
    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_String $value The ChangesetValue_String
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($value) {
            $html .= $hp->purify($value->getText(), CODENDI_PURIFIER_CONVERT_HTML);
        }
        return $html;
    }
    
    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return boolean
     */
    public function takesTwoColumns() {
        return $this->getProperty('size') > 40;
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
        $r1 = $this->getRuleString();
        $r2 = $this->getRuleNoCr();
        if (!($is_valid = $r1->isValid($value))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_string_value', array($this->getLabel())));
        } else if (!($is_valid = $r2->isValid($value))) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_artifact', 'error_string_value_characters', array($this->getLabel())));
        }
        return $is_valid;
    }
    
    protected function getRuleNoCr() {
        return new Rule_NoCr();
    }
}
?>
