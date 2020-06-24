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
                    $title    = 'title="' . dgettext('tuleap-tracker', 'We are unable to update the computed field. The target field should be equal to the computed field\'s name.') . '"';
                }

                $html .= '
                        <input type="hidden"
                               name="formElement_data[specific_properties][' . $key . ']"
                               value="0">';
                $html .= '<div class="alert alert-warning">';
                $html .= dgettext('tuleap-tracker', '<h5>Please update this computed field</h5><p>The legacy Computed fields will be gone in Tuleap 9.4.<br/>Upon update, the following changes will take effect:</p><ul><li>The computation will take into account all linked artifacts recursively, regardless of link type.</li><li>The target field will no longer be configurable, the field\'s name will now be used instead.</li><li>Permissions will not be taken into account.</li></ul><p>For example, a user can see artifact A linked to B and C. Artifact A has a computed field called "Remaining effort".<br/>Even if user cannot access artifacts B or C, the "Remaining effort" field in A will sum the values from the "Remaining Effort" fields in B and C.</p>');
                $html .= '<h6>' . dgettext('tuleap-tracker', 'We are unable to update the computed field. The target field should be equal to the computed field\'s name.') . '</h6>';
                $html .= '<button class="btn"
                                    name="formElement_data[specific_properties][' . $key . ']"
                                    value="1"
                                    ' . $title . '
                                    ' . $disabled . '
                                    type="submit">';
                $html .= dgettext('tuleap-tracker', 'Update to overridable computed field');
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
                    $html .= '<h6>' . dgettext('tuleap-tracker', 'We are unable to update the computed field. The target field should be equal to the computed field\'s name.') . '</h6>';
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
