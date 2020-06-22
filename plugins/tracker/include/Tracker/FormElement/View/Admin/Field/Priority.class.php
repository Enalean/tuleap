<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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


class Tracker_FormElement_View_Admin_Priority extends Tracker_FormElement_View_Admin
{

    /**
     * Fetch the "required" part of field admin
     *
     * @return string the HTML for the part of form for required checkbox
     */
    protected function fetchRequired()
    {
        return '';
    }

    public function fetchAdminSpecificProperties()
    {
        return '';
    }

    protected function fetchCustomHelp()
    {
        $html  = '<span class="tracker-admin-form-element-help">';
        $html .= dgettext('tuleap-tracker', 'Display the artifact rank in the context of a milestone. The value is given by the AgileDashboard plugin. Please note that this field will evolve in the future.');
        $html .= '</span>';

        return $html;
    }
}
