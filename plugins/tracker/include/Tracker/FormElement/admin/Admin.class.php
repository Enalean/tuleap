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
        $html .= $this->fetchTypeForUpdate();
        $html .= $this->fetchNameForUpdate();
        $html .= $this->fetchLabelForUpdate();
        $html .= $this->fetchDescriptionForUpdate();
        $html .= $this->fetchRanking();
        return $html;
    }
    
    public function fetchAdminForShared() {
        $html = '';
        $html .= $this->fetchTypeNotModifiable();
        $html .= $this->fetchCustomHelpForShared();
        $html .= $this->fetchNameForShared();
        $html .= $this->fetchLabelForShared();
        $html .= $this->fetchDescriptionForShared();
        $html .= $this->fetchRanking();
        return $html;
    }
    
    public function fetchAdminForCreate() {
        $html = '';
        $html .= $this->fetchTypeNotModifiable();
        $html .= $this->fetchLabelForUpdate();
        $html .= $this->fetchDescriptionForUpdate();
        $html .= $this->fetchRanking();
        return $html;
    }
    
    protected function fetchTypeNotModifiable() {
        $html = '';
        $html .= '<p><label for="formElement_type">'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'type') .': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="'. $this->formElement->getFactoryIconUseIt() .'" style="vertical-align:middle"/> '. $this->formElement->getFactoryLabel();
        $html .= '</p>';
        return $html;
    }
    
    protected function fetchTypeForUpdate() {
        $html = '';
        $html .= '<p><label for="formElement_type">'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'type') .': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="'. $this->formElement->getFactoryIconUseIt() .'" style="vertical-align:middle"/> '. $this->formElement->getFactoryLabel();

        $html .= '</p>';
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
        
    protected function fetchCustomHelp() {
        return '';
    }
  
    protected function fetchCustomHelpForShared() {
        $originalTrackerName = 'Xxx';
        $originalProjectName = 'Yyy';
        $html = '';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_type', 'field_copied_from', array($originalTrackerName, $originalProjectName));
        $html .= '</span>';
        return $html;
    }
    
    protected function fetchRanking() {
        $html = '';
        $html .= '<p>';
        $html .= '<label for="formElement_rank">'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'rank_screen').': <font color="red">*</font></label>';
        $html .= '<br />';
        $adminFactory = new Tracker_FormElement_AdminFactory();
        $items = array();
        foreach (Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this->formElement->getTracker()) as $field) {
            $items[] = $field->getRankSelectboxDefinition();
        }
        $html .= $GLOBALS['HTML']->selectRank(
            $this->formElement->id, 
            $this->formElement->rank, 
            $items, 
            array(
                'id'   => 'formElement_rank',
                'name' => 'formElement_data[rank]'
            )
        );
        $html .= '</p>';
        return $html;
    }
}

?>
