<?php
/** Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_FormElement_View_Admin_Field_LastModifiedBy extends Tracker_FormElement_View_Admin_Field_List
{

    protected function fetchCustomHelp()
    {
        $html = '';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= dgettext('tuleap-tracker', 'The field is automatically set to the last person who modified the artifact');
        $html .= '</span>';
        return $html;
    }

    protected function fetchRequired()
    {
        return '';
    }

    /**
     * Fetch additionnal stuff to display below the create form
     * Result if not empty must be enclosed in a <tr>
     *
     * @return string html
     */
    public function fetchAfterAdminCreateForm()
    {
        // Don't display the values because this is a special field
        return '';
    }

    /**
     * Fetch additionnal stuff to display below the edit form
     *
     * @return string html
     */
    public function fetchAfterAdminEditForm()
    {
        // Don't display the values because this is a special field
        return '';
    }
}
