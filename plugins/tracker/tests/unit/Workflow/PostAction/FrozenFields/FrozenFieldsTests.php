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

use SimpleXMLElement;
use Transition;
use Tuleap\Tracker\Test\Builders\Fields\StringFieldBuilder;

final class FrozenFieldsTests extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testExportsTheActionInXML(): void
    {
        $field_01 = StringFieldBuilder::aStringField(101)->build();
        $field_02 = StringFieldBuilder::aStringField(102)->build();

        $transition = $this->createMock(Transition::class);

        $frozen_fields = new FrozenFields($transition, 1, [$field_01, $field_02]);

        $root_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><postactions/>');
        $mapping  = [
            'F101' => 101,
            'F102' => 102,
        ];

        $frozen_fields->exportToXml($root_xml, $mapping);

        $this->assertCount(1, $root_xml->postaction_frozen_fields);
        $this->assertCount(2, $root_xml->postaction_frozen_fields->field_id);

        self::assertSame((string) $root_xml->postaction_frozen_fields->field_id[0]['REF'], 'F101');
        self::assertSame((string) $root_xml->postaction_frozen_fields->field_id[1]['REF'], 'F102');
    }
}
