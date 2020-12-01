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


class Tracker_FormElement_View_Admin_Field_LastUpdateDate extends Tracker_FormElement_View_Admin_Field
{

    public function fetchCustomHelp()
    {
        $html = '';
        $html .= '<span class="tracker-admin-form-element-help">';
        $html .= dgettext('tuleap-tracker', 'The field is automatically set to last artifact update date');
        $html .= '</span>';
        return $html;
    }

    /**
     * Fetch the "required" part of field admin
     *
     * @return string the HTML for the part of form for required checkbox
     */
    protected function fetchRequired()
    {
        return '';
    }
}
