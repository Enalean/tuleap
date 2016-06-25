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
        $html = '';
        switch ($property['type']) {
            case 'string':
                if ($this->isMigrated()) {
                    break;
                }
                $html .= parent::fetchAdminSpecificProperty($key, $property);
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
        return $this->canUpgrade() && $this->formElement->useFastCompute();
    }
}
