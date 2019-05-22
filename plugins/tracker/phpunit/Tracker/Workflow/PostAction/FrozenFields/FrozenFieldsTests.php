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

namespace Tuleap\Tracker\Workflow\PostAction\FrozenFields;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use SimpleXMLElement;
use Tracker_FormElement_Field;
use Transition;

class FrozenFieldsTests extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testExportsTheActionInXML()
    {
        $field_01 = Mockery::mock(Tracker_FormElement_Field::class);
        $field_02 = Mockery::mock(Tracker_FormElement_Field::class);

        $field_01->shouldReceive('getId')->andReturn(101);
        $field_02->shouldReceive('getId')->andReturn(102);

        $transition = Mockery::mock(Transition::class);

        $frozen_fields = new FrozenFields($transition, 1, [$field_01, $field_02]);

        $root_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><postactions/>');
        $mapping  = [
            'F101' => 101,
            'F102' => 102,
        ];

        $frozen_fields->exportToXml($root_xml, $mapping);

        $this->assertCount(1, $root_xml->postaction_frozen_fields);
        $this->assertCount(2, $root_xml->postaction_frozen_fields->field_id);

        $this->assertSame((string) $root_xml->postaction_frozen_fields->field_id[0]['REF'], 'F101');
        $this->assertSame((string) $root_xml->postaction_frozen_fields->field_id[1]['REF'], 'F102');
    }
}
