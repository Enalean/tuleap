<?php
/*
 * Copyright (C) Enalean, 2016. All Rights Reserved
 *
 * This program is free software; you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Tracker\FormElement\View\Admin\Field;

use \Tracker_FormElement_View_Admin_Field;

class Computed extends Tracker_FormElement_View_Admin_Field
{
    protected function fetchAdminSpecificProperty($key, $property)
    {
        if ($property['type'] !== 'upgrade_button') {
            return parent::fetchAdminSpecificProperty($key, $property);
        }

        $html = '';
        $html .= '
                <input type="hidden"
                       name="formElement_data[specific_properties]['. $key .']"
                       value="0">';
        $html .= '<div class="alert alert-warning">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_description');
        $html .= '<button class="btn"
                            name="formElement_data[specific_properties]['. $key .']"
                            value="1"
                            type="submit">';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_button');
        $html .= '</button>';
        $html .= '</div>';

        return $html;
    }
}
