<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 * 
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class Tracker_FormElement_Admin {
    /**
     * @var Tracker_FormElement
     */
    protected $formElement;
    
    public function __construct(Tracker_FormElement $formElement) {
        $this->formElement = $formElement;
    }
    
    public function fetchAdminForUpdate() {
        $html = '';
        //$html .= $this->fetchNameForUpdate();
        $html .= $this->fetchLabelForUpdate();
        $html .= $this->fetchDescriptionForUpdate();
        return $html;
    }
    
    public function fetchNameForUpdate() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<p>';
        $html .= '<label for="formElement_name">' . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'name') . ': </label><br />';
        $html .= '<input type="text" id="formElement_name" name="formElement_data[name]" value="' . $hp->purify($this->formElement->getName(), CODENDI_PURIFIER_CONVERT_HTML) . '" />';
        $html .= '</p>';
        return $html;
    }

    /**
     * html form for the label 
     *
     * @return string html
     */
    public function fetchLabelForUpdate() {
        $html = '';
        $html .= '<p>';
        $html .= '<label for="formElement_label">'.$GLOBALS['Language']->getText('plugin_tracker_include_report', 'field_label').': <font color="red">*</font></label> ';
        $html .= '<br />';
        $html .= '<input type="text" name="formElement_data[label]" id="formElement_label" value="'. $this->formElement->getLabel() .'" size="40" />';
        $html .= '<input type="hidden" name="formElement_data[use_it]" value="1" />';
        $html .= '</p>';
        $html .= $this->fetchCustomHelp();
        return $html;
    }
    
    /**
     * html form for the description 
     *
     * @return string html
     */
    public function fetchDescriptionForUpdate() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<p>';
        
        $html .= '<label for="formElement_description">'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'fieldset_desc').':</label>';
        $html .= '<br />';
        $html .= '<textarea name="formElement_data[description]" id="formElement_description" cols="40">'.  $hp->purify($this->formElement->description, CODENDI_PURIFIER_CONVERT_HTML)  .'</textarea>';

        $html .= '</p>';
        return $html;
    }
    
    public function fetchAdminForShared() {
        $html = '';
        //$html .= $this->fetchNameForUpdate();
        $html .= $this->fetchLabelForShared();
        $html .= $this->fetchDescriptionForShared();
        return $html;
    }
    
    public function fetchNameForShared() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_type', 'name') . ': ';
        $html .= '<br />';
        $html .= $hp->purify($this->formElement->getName(), CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</p>';
        return $html;
    }
    
    /**
     * html form for the label 
     *
     * @return string html
     */
    public function fetchLabelForShared() {
        $html = '';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_report', 'field_label').': ';
        $html .= '<br />';
        $html .= $this->formElement->getLabel();
        $html .= '</p>';
        $html .= $this->fetchCustomHelp();
        return $html;
    }
    
    /**
     * html form for the description 
     *
     * @return string html
     */
    public function fetchDescriptionForShared() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_type', 'fieldset_desc').': ';
        $html .= '<br />';
        $html .= $hp->purify($this->formElement->description, CODENDI_PURIFIER_CONVERT_HTML);
        $html .= '</p>';
        return $html;
    }
    
    public function fetchAdminForCreate() {
        $html = '';
        $html .= $this->fetchLabelForUpdate();
        $html .= $this->fetchDescriptionForUpdate();
        return $html;
    }
    
    protected function fetchCustomHelp() {
        return '';
    }
}

?>
