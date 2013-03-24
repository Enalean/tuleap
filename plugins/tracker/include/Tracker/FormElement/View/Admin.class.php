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
 

/**
 * Manage display of FormElement administration (creation / update).
 * 
 * This is the top most element of the hierarchy and correspond to Tracker_FormElement
 */
class Tracker_FormElement_View_Admin {
    
    /**
     * @var Tracker_FormElement
     */
    protected $formElement;
    
    /**
     * @var 
     */
    protected $allUsedElements;
    
    public function __construct(Tracker_FormElement $formElement, $allUsedElements) {
        $this->formElement     = $formElement;
        $this->allUsedElements = $allUsedElements;
    }
    
    public function fetchTypeNotModifiable() {
        $html = '';
        $html .= '<p><label for="formElement_type">'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'type') .': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="'. $this->formElement->getFactoryIconUseIt() .'" style="vertical-align:middle"/> '. $this->formElement->getFactoryLabel();
        $html .= '</p>';
        $html .= '<p>'.$this->formElement->getFactoryDescription().'</p>';
        return $html;
    }
    
    public function fetchTypeForUpdate() {
        $html = '';
        $html .= '<p><label for="formElement_type">'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'type') .': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="'. $this->formElement->getFactoryIconUseIt() .'" style="vertical-align:middle"/> '. $this->formElement->getFactoryLabel();
        $html .= '<p>'.$this->formElement->getFactoryDescription().'</p>';
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
  
    public function fetchCustomHelpForShared() {
        $originalTrackerName = $this->formElement->getOriginalTracker()->getName();
        $originalProjectName = $this->formElement->getOriginalProject()->getPublicName();
        $originalEditUrl     = $this->formElement->getOriginalField()->getAdminEditUrl();
        
        $html = '';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_include_type', 'field_copied_from', array($originalTrackerName, $originalProjectName, $originalEditUrl));
        $html .= '</span>';
        return $html;
    }
    
    public function fetchRanking() {
        $html = '';
        $html .= '<p>';
        $html .= '<label for="formElement_rank">'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'rank_screen').': <font color="red">*</font></label>';
        $html .= '<br />';
        $items = array();
        foreach ($this->allUsedElements as $field) {
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
    
    /**
     * If the formElement has specific properties then this method 
     * should return the html needed to update those properties
     * 
     * The html must be a (or many) html row(s) table (one column for the label, 
     * another one for the property)
     * 
     * <code>
     * <tr><td><label>Property 1:</label></td><td><input type="text" value="value 1" /></td></tr>
     * <tr><td><label>Property 2:</label></td><td><input type="text" value="value 2" /></td></tr>
     * </code>
     * 
     * @return string html
     */
    public function fetchAdminSpecificProperties() {
        $html = '';
        foreach ($this->formElement->getProperties() as $key => $property) {
            $html .= '<p>';
            $html .= '<label for="formElement_properties_'. $key .'">'. $this->formElement->getPropertyLabel($key) .'</label>: ';
            $html .= '<br />';
            $html .= $this->fetchAdminSpecificProperty($key, $property);
            $html .= '</p>';
        }
        return $html;
    }
    
    /**
     * Fetch a unique property edit form
     * 
     * @param string $key      The key of the property
     * @param array  $property The property to display
     *
     * @see fetchAdminSpecificProperties
     * @return string html
     */
    protected function fetchAdminSpecificProperty($key, $property) {
        
        $html = '';
        switch ($property['type']) {
        case 'string':
            $html .= '<input type="text" 
                             size="'. $property['size'] .'"
                             name="formElement_data[specific_properties]['. $key .']" 
                             id="formElement_properties_'. $key .'" 
                             value="'. $property['value'] .'" />';
            break;
        case 'date':
            $value = $property['value'] ? $this->formElement->formatDate($property['value']) : '';
            $html .= $GLOBALS['HTML']->getDatePicker("formElement_properties_".$key, "formElement_data[specific_properties][$key]", $value);
            break;
        case 'rich_text':
            $html .= '<textarea
                           class="tracker-field-richtext"
                           cols="50" rows="10"  
                           name="formElement_data[specific_properties]['. $key .']" 
                           id="formElement_properties_'. $key .'">' .
                       $property['value'] . '</textarea>';
            break;
        case 'radio':
            foreach ($property['choices'] as $key_choice => $choice) {
                $checked = '';
                if ($this->formElement->getProperty($key) == $choice['radio_value']) {
                    $checked = 'checked="checked"';
                }
                $html .= '<input type="radio" 
                                 name="formElement_data[specific_properties]['. $key .']" 
                                 value="'. $choice['radio_value'] .'" 
                                 id="formElement_properties_'. $key_choice .'" 
                                 '. $checked .' />';
                $html .= $this->fetchAdminSpecificProperty($key_choice, $choice);
                $html .= '<br />';
            }
            break;
        case 'label':
            $html .= '<label for="formElement_properties_'. $key .'">'. $this->formElement->getPropertyLabel($key) .'</label>';
            
        default:
            //Unknown type. raise exception?
            break;
        }
        return $html;
    }
    
    /**
     * Fetch additionnal stuff to display below the edit form
     *
     * @return string html
     */
    public function fetchAfterAdminEditForm() {
        return '';
    }
    
    /**
     * Fetch additionnal stuff to display below the create form
     * Result if not empty must be enclosed in a <tr>
     *
     * @return string html
     */
    public function fetchAfterAdminCreateForm() {
        return '';
    }
    
    public function fetchAdminButton($name) {
        $html  = '';
        $html .= '<p>';
        $html .= '<input type="submit" name="'. $name .'" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        $html .= '</p>';
        return $html;
    }
    
    /**
     * fetch permission link on admin form
     *
     * @return string html
     */
    public function fetchAdminFormPermissionLink() {
        $html = '';
        $html .= '<p>';
        $html .= '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(
            array(
                'tracker'     => $this->formElement->tracker_id,
                'func'        => 'admin-perms-fields',
                'selected_id' => $this->formElement->id
            )
        ) .'">';
        $html .= $GLOBALS['HTML']->getImage('ic/lock-small.png', array(
            'style' => 'vertical-align:middle;',
        ));
        $html .= ' ';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','edit_permissions') .'</a>';
        $html .= '</p>';
        return $html;
    }
    
    public function fetchSharedUsage() {
        $html = '';
        $fields = $this->formElement->getSharedTargets();
        if ($fields) {
            $trackers = array();
            foreach ($fields as $field) {
                $t = $field->getTracker();
                $trackers[$t->getId()] = '<a href="'. TRACKER_BASE_URL.'/?tracker='. $t->getId() .'&func=admin-formElements">'. $t->getName() .' ('. $t->getProject()->getPublicName() .')</a>';
            }
            $html .= $GLOBALS['Language']->getText('plugin_tracker_include_type', 'field_copied_to');
            $html .= '<ul><li>';
            $html .= implode('</li><li>', $trackers);
            $html .= '</li></ul>';
        }
        return $html;
    }
}

?>
