<?php
/**
 * Copyright (C) Enalean, 2016 - Present. All Rights Reserved
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

use Tracker_FormElement_View_Admin_Field;

class Computed extends Tracker_FormElement_View_Admin_Field
{
    private const DEFAULT_VALUE_KEY = 'default_value';

    protected function fetchAdminSpecificProperty($key, $property)
    {
        $html = '';
        switch ($property['type']) {
            case 'upgrade_button':
                if ($this->isMigrated()) {
                    break;
                }

                $disabled = '';
                $title    = '';
                if (! $this->canUpgrade()) {
                    $disabled = 'disabled="disabled"';
                    $title    = 'title="' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_set_target_field') . '"';
                }

                $html .= '
                        <input type="hidden"
                               name="formElement_data[specific_properties][' . $key . ']"
                               value="0">';
                $html .= '<div class="alert alert-warning">';
                $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_description');
                $html .= '<h6>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_set_target_field') . '</h6>';
                $html .= '<button class="btn"
                                    name="formElement_data[specific_properties][' . $key . ']"
                                    value="1"
                                    ' . $title . '
                                    ' . $disabled . '
                                    type="submit">';
                $html .= $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_button');
                $html .= '</button>';
                $html .= '</div>';
                break;
            case 'string':
                if ($key === self::DEFAULT_VALUE_KEY) {
                    $html .= parent::fetchAdminSpecificProperty($key, $property);
                }

                if ($this->isMigrated()) {
                    break;
                }

                if (! $this->canUpgrade()) {
                    $html .= '<div class="alert alert-warning">';
                    $html .= '<h6>' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'upgrade_computed_field_set_target_field') . '</h6>';
                }
                $html .= parent::fetchAdminSpecificProperty($key, $property);

                if (! $this->canUpgrade()) {
                    $html .= '</div>';
                }
                break;
            default:
                return parent::fetchAdminSpecificProperty($key, $property);
                break;
        }

        return $html;
    }

    private function canUpgrade()
    {
        return $this->formElement->name === $this->formElement->getProperty('target_field_name');
    }

    private function isMigrated()
    {
        return $this->canUpgrade();
    }
}
