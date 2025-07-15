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

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use SimpleXMLElement;
use Tracker_FormElement_Container_Column;
use Tracker_FormElement_Field_Float;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\Field\Text\TextField;
use Tuleap\Tracker\Test\Builders\Fields\ColumnContainerBuilder;
use Tuleap\Tracker\Test\Builders\Fields\DateFieldBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Container_ColumnTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testIsNotDeletableWithFields(): void
    {
        $a_formelement    = DateFieldBuilder::aDateField(650)->build();
        $container_column = ColumnContainerBuilder::aColumn(651)->containsFormElements($a_formelement)->build();

        self::assertFalse($container_column->canBeRemovedFromUsage());
    }

    public function testIsDeletableWithoutFields(): void
    {
        $container_column = ColumnContainerBuilder::aColumn(651)->build();

        self::assertTrue($container_column->canBeRemovedFromUsage());
    }

    public function testItCallsExportPermissionsToXMLForEachSubfield(): void
    {
        $field_01 = $this->createMock(StringField::class);
        $field_02 = $this->createMock(Tracker_FormElement_Field_Float::class);
        $field_03 = $this->createMock(TextField::class);

        $container_column = $this->createPartialMock(Tracker_FormElement_Container_Column::class, ['getAllFormElements']);
        $container_column->method('getAllFormElements')->willReturn([$field_01, $field_02, $field_03]);

        $data    = '<?xml version="1.0" encoding="UTF-8"?>
                    <permissions/>';
        $xml     = new SimpleXMLElement($data);
        $mapping = [];
        $ugroups = [];

        $field_01->expects($this->once())->method('exportPermissionsToXML');
        $field_02->expects($this->once())->method('exportPermissionsToXML');
        $field_03->expects($this->once())->method('exportPermissionsToXML');

        $container_column->exportPermissionsToXML($xml, $ugroups, $mapping);
    }
}
