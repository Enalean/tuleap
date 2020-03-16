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


class Tracker_FormElement_View_Admin_Field extends Tracker_FormElement_View_Admin
{

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
    public function fetchAdminSpecificProperties()
    {
        $html = '';

        //required
        $html .= $this->fetchRequired();

        //notifications
        $html .= $this->fetchNotifications();

        $html .= parent::fetchAdminSpecificProperties();

        return $html;
    }

    /**
     * Fetch the "notifications" part of field admin
     *
     * @return string html
     */
    protected function fetchNotifications()
    {
        $html = '';
        if ($this->formElement->isNotificationsSupported()) {
            $html .= ' <p>';
            $html .= ' <label for="formElement_notifications" class="checkbox">';
            $html .= '<input type="hidden" name="formElement_data[notifications]" value="0" />';
            $html .= '<input type="checkbox" name="formElement_data[notifications]" id="formElement_notifications" value="1" ' . ($this->formElement->notifications ? 'checked="checked"' : '') . '" />';
            $html .= ' ' . $GLOBALS['Language']->getText('plugin_tracker_common_field', 'notifications');
            $html .= '</label>';
            $html .= '</p>';
        }

        return $html;
    }

    /**
     * Fetch the "required" part of field admin
     *
     * @return string the HTML for the part of form for required checkbox
     */
    protected function fetchRequired()
    {
        $html = '';
        $html .= '<p>';
        $html .= '<input type="hidden" name="formElement_data[required]" value="0" />';
        $html .= '<label class="checkbox">';
        $html .= '<input type="checkbox" name="formElement_data[required]" id="formElement_required" value="1" ' . ($this->formElement->required ? 'checked="checked"' : '') . '" />';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_common_field', 'required');
        $html .= '</label>';
        $html .= '</p>';
        return $html;
    }
}
