<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'Admin.class.php';

class Tracker_FormElement_Admin_Shared extends Tracker_FormElement_Admin {

    public function __construct(Tracker_FormElement_Shared $formElement, $allUsedElements) {
        $this->formElement     = $formElement;
        $this->allUsedElements = $allUsedElements;
    }
    
     public function fetchAdminForCreate() {
        $html = '';
        $html .= '<p>Field id:';
        $html .=  '<input type="text" name="formElement_data[field_id]" value="" />';
        $html .=  '</p>';
        $html .= $this->fetchAdminButton(self::SUBMIT_CREATE);
        return $html;
    }

}

?>
