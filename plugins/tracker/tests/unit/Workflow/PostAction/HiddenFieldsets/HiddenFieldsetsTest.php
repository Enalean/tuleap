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

namespace Tuleap\Tracker\Workflow\PostAction\HiddenFieldsets;

use SimpleXMLElement;
use Transition;
use Tuleap\Tracker\FormElement\Container\Fieldset\FieldsetContainer;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class HiddenFieldsetsTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testExportsTheActionInXML(): void
    {
        $fieldset_01 = $this->createMock(FieldsetContainer::class);
        $fieldset_02 = $this->createMock(FieldsetContainer::class);

        $fieldset_01->method('getID')->willReturn(101);
        $fieldset_02->method('getID')->willReturn(102);

        $transition = $this->createMock(Transition::class);

        $hidden_fieldsets = new HiddenFieldsets($transition, 1, [$fieldset_01, $fieldset_02]);

        $root_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><postactions/>');
        $mapping  = [
            'F101' => 101,
            'F102' => 102,
        ];

        $hidden_fieldsets->exportToXml($root_xml, $mapping);

        $this->assertCount(1, $root_xml->postaction_hidden_fieldsets);
        $this->assertCount(2, $root_xml->postaction_hidden_fieldsets->fieldset_id);

        self::assertSame((string) $root_xml->postaction_hidden_fieldsets->fieldset_id[0]['REF'], 'F101');
        self::assertSame((string) $root_xml->postaction_hidden_fieldsets->fieldset_id[1]['REF'], 'F102');
    }
}
