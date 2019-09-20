<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('bootstrap.php');

class Tracker_FormElement_StaticField_SeparatorTest extends TuleapTestCase
{

    public function testFetchDescription()
    {
        $expected_message = '';
        $id = 2;
        $tracker_id = 254;
        $parent_id = 0;
        $name = 'separator2';
        $label = 'Separator Label';
        $description = 'Separator Description that should not be kept';
        $use_it = true;
        $scope = 'S';
        $required = false;
        $notifications = false;
        $rank = 25;
        $original_field = null;

        $separator = new Tracker_FormElement_StaticField_Separator($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank, $original_field);

        $this->assertEqual('Separator Label', $separator->getLabel());
        $this->assertEqual('', $separator->getDescription());
        $this->assertEqual($expected_message, $separator->getCannotRemoveMessage());
    }
}
