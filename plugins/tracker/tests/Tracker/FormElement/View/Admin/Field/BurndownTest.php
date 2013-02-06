<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class Tracker_FormElement_View_Admin_Field_Burndown_fetchAdminSpecificPropertiesTest extends TuleapTestCase {

    public function itDisablesTheCapacityCheckboxIfCapacityFieldDoesNotExist() {
        $burndown_field = mock('Tracker_FormElement_Field_Burndown');
        $allUsedElements = array();
        
        stub($burndown_field)->doesCapacityFieldExist()->returns(false);
        
        $admin_view = new Tracker_FormElement_View_Admin_Field_Burndown($burndown_field, $allUsedElements);

        $html = $admin_view->fetchAdminSpecificProperties();
        $this->assertStringContains($html, 'disabled="disabled"');
    }

    public function itEnablesTheCapacityCheckboxIfCapacityFieldExists() {
        $burndown_field = mock('Tracker_FormElement_Field_Burndown');
        $allUsedElements = array();

        stub($burndown_field)->doesCapacityFieldExist()->returns(true);

        $admin_view = new Tracker_FormElement_View_Admin_Field_Burndown($burndown_field, $allUsedElements);

        $html = $admin_view->fetchAdminSpecificProperties();
        $this->assertFalse(strstr($html, 'disabled'));
    }

    public function itTicksTheCapacityCheckboxIfBurndownUsesCapacityField() {
        $burndown_field = mock('Tracker_FormElement_Field_Burndown');
        $allUsedElements = array();

        stub($burndown_field)->doesBurndownUseCapacityField()->returns(true);

        $admin_view = new Tracker_FormElement_View_Admin_Field_Burndown($burndown_field, $allUsedElements);

        $html = $admin_view->fetchAdminSpecificProperties();
        $this->assertStringContains($html, 'checked="checked"');
    }

    public function itDoesNotTickTheCapacityCheckboxIfBurndownDoesNotUseCapacityField() {
        $burndown_field = mock('Tracker_FormElement_Field_Burndown');
        $allUsedElements = array();

        stub($burndown_field)->doesBurndownUseCapacityField()->returns(false);

        $admin_view = new Tracker_FormElement_View_Admin_Field_Burndown($burndown_field, $allUsedElements);

        $html = $admin_view->fetchAdminSpecificProperties();
         $this->assertFalse(strstr($html, 'checked'));
    }
}
?>
