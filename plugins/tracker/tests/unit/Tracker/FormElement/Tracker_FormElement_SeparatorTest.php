<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\TestCase;
use Tracker_FormElement_StaticField_Separator;

//phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_SeparatorTest extends TestCase
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

        $this->assertEquals('Separator Label', $separator->getLabel());
        $this->assertEquals('', $separator->getDescription());
        $this->assertEquals($expected_message, $separator->getCannotRemoveMessage());
    }
}
