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


class Tracker_FormElement_View_Admin_Field_Selectbox extends Tracker_FormElement_View_Admin_Field_List {

    protected $availableTypes = array('msb', 'cb');
    
    public function fetchTypeForUpdate() {
        $html = '';
        $html .= '<p><label for="formElement_type">'. $GLOBALS['Language']->getText('plugin_tracker_include_type', 'type') .': </label><br />';
        $html .= '<img width="16" height="16" alt="" src="'. $this->formElement->getFactoryIconUseIt() .'" style="vertical-align:middle"/> '. $this->formElement->getFactoryLabel();

           //do not change from SB to MSB if the field is used to define the workflow
        $wf = WorkflowFactory::instance();
        if (!$wf->isWorkflowField($this->formElement)) {
            $html .= ' (';
            
            $change_links = array();

            foreach($this->availableTypes as $type) {
                $change_links[] = '<a href="'.TRACKER_BASE_URL.'/?'. http_build_query(array(
                        'tracker'            => $this->formElement->tracker_id,
                        'func'               => 'admin-formElement-update',
                        'formElement'        => $this->formElement->id,
                        'change-type'        => $type
                    )) .'" onclick="return confirm(\''.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','switch_type_confirm').'\');">'
                       .$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','switch_'. $type ).'</a> ';
            }
            $html .= implode($change_links,', ');
            $html .= ')';   
        }

        $html .= '</p>';
        return $html;
    }
    
 
}

?>
