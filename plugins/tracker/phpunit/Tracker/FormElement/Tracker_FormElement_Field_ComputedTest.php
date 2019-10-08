<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Field_Computed;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_FormElement_Field_ComputedTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItExportsDefaultValueInXML()
    {
        $computed_field = \Mockery::mock(Tracker_FormElement_Field_Computed::class)->makePartial();
        $computed_field->shouldReceive('getProperty')->with('default_value')->andReturn('12.34');

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        $this->assertSame("12.34", (string) $xml->properties['default_value']);
    }

    public function testItDoesNotExportNullDefaultValueInXML()
    {
        $computed_field = \Mockery::mock(Tracker_FormElement_Field_Computed::class)->makePartial();
        $computed_field->shouldReceive('getProperty')->with('default_value')->andReturnNull();

        $xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><field />');
        $computed_field->exportPropertiesToXML($xml);

        $this->assertFalse(isset($xml->properties));
    }
}
