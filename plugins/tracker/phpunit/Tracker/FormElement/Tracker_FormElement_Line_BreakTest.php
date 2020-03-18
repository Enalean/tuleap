<?php
/**
 * Copyright Enalean (c) 2020 - Present. All rights reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
declare(strict_types = 1);

final class Tracker_FormElement_Line_BreakTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration, \Tuleap\GlobalLanguageMock;
    public function testFetchDescription(): void
    {
        $id = 2;
        $tracker_id = 254;
        $parent_id = 0;
        $name = 'linebreak2';
        $label = 'Line Break Label';
        $description = 'Line Break Description that should not be kept';
        $use_it = true;
        $scope = 'S';
        $required = false;
        $notififcations = false;
        $rank = 25;
        $original_field = null;

        $line_break = new Tracker_FormElement_StaticField_LineBreak($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notififcations, $rank, $original_field);

        $this->assertEquals('Line Break Label', $line_break->getLabel());
        $this->assertEquals('', $line_break->getDescription());
        $this->assertEquals('', $line_break->getCannotRemoveMessage());
    }
}
