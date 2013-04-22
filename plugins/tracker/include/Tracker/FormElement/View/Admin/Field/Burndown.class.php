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


class Tracker_FormElement_View_Admin_Field_Burndown extends Tracker_FormElement_View_Admin_Field {
    
    public function fetchAdminSpecificProperties() {
        $html = '';
        
        //required
        $html .= $this->fetchRequired();

        $html .= $this->fetchExcludeWeekEnds();

        return $html;
    }
    
    protected function fetchRequired() {
        return '';
    }
    
    /**
     * Fetch "exclude week ends" part of field admin
     * 
     * @return string the HTML for the checkbox part
     */
    protected function fetchExcludeWeekEnds() {
        $key  = 'exclude_weekends';
        $html = '';
        $html .= '<p>';
        $html .= '<input type="hidden" name="formElement_data[specific_properties]['. $key .']" value="0" />';
        $html .= '<input type="checkbox" name="formElement_data[specific_properties]['. $key .']" id="formElement_properties_'. $key .'" value="1" '. ($this->formElement->excludeWeekends() ? 'checked="checked"' : '') .'" />';
        $html .= '<label for="formElement_properties_'. $key .'">'. $this->formElement->getPropertyLabel($key) .'</label>';
        $html .= '</p>';
        return $html;
    }


}

?>
