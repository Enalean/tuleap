<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Field_Date;
use Tracker_FormElement_Field_Float;
use Tracker_FormElement_Field_String;
use Tracker_FormElement_Field_Text;

class Tracker_FormElement_Container_ColumnTest extends TestCase //phpcs:ignore
{
    use MockeryPHPUnitIntegration;

    public function testIsNotDeletableWithFields()
    {
        $container_column = Mockery::mock(Tracker_FormElement_Container_Column::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $a_formelement = Mockery::mock(Tracker_FormElement_Field_Date::class);

        $container_column->shouldReceive('getFormElements')->andReturn([$a_formelement]);

        $this->assertFalse($container_column->canBeRemovedFromUsage());
    }

    public function testIsDeletableWithoutFields()
    {
        $container_column = Mockery::mock(Tracker_FormElement_Container_Column::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $container_column->shouldReceive('getFormElements')->andReturn(null);

        $this->assertTrue($container_column->canBeRemovedFromUsage());
    }

    public function testItCallsExportPermissionsToXMLForEachSubfield()
    {
        $container_column = Mockery::mock(Tracker_FormElement_Container_Column::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $field_01 = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field_02 = Mockery::mock(Tracker_FormElement_Field_Float::class);
        $field_03 = Mockery::mock(Tracker_FormElement_Field_Text::class);

        $container_column->shouldReceive('getAllFormElements')->andReturn(
            [
            $field_01,
            $field_02,
                $field_03
            ]
        );

        $data    = '<?xml version="1.0" encoding="UTF-8"?>
                    <permissions/>';
        $xml     = new SimpleXMLElement($data);
        $mapping = [];
        $ugroups = [];

        $field_01->shouldReceive('exportPermissionsToXML')->once();
        $field_02->shouldReceive('exportPermissionsToXML')->once();
        $field_03->shouldReceive('exportPermissionsToXML')->once();

        $container_column->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}
