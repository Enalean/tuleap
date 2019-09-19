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
 * Visit a target shared element and provide a creation view
 */
class Tracker_FormElement_View_Admin_CreateSharedVisitor extends Tracker_FormElement_View_Admin_CreateVisitor
{

    protected function fetchForm()
    {
        $html = '';
        $html .= '<p>Field id:';
        $html .=  '<input type="text" name="formElement_data[field_id]" value="" />';
        $html .=  '</p>';
        $html .= $this->adminElement->fetchAdminButton(self::SUBMIT_CREATE);
        return $html;
    }
}
