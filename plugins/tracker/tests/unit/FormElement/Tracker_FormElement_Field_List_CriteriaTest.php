<?php
/**
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

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use SimpleXMLElement;
use Tracker_Report_Criteria;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListField;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserBindBuilder;
use Tuleap\Tracker\Test\Builders\Fields\SelectboxFieldBuilder;
use Tuleap\Tracker\Test\Builders\ReportTestBuilder;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_CriteriaTest extends TestCase // phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private ListField $field;
    private Tracker_Report_Criteria&MockObject $criteria;

    public function setUp(): void
    {
        $this->field    = SelectboxFieldBuilder::aSelectboxField(456)->build();
        $this->criteria = $this->createMock(Tracker_Report_Criteria::class);
    }

    public function testItSetsCriteriaValueFromXML(): void
    {
        $report_id = 'XML_IMPORT_' . bin2hex(random_bytes(32));
        $report    = ReportTestBuilder::aPublicReport()->withId($report_id)->build();
        $this->criteria->method('getReport')->willReturn($report);

        ListStaticBindBuilder::aStaticBind($this->field)->build();

        $xml_criteria_value = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <criteria_value type="list">
                <selected_value REF="V1"/>
            </criteria_value>
        ');

        $value_01 = ListStaticValueBuilder::aStaticValue('value 1')->build();
        $value_02 = ListStaticValueBuilder::aStaticValue('value 2')->build();
        $mapping  = [
            'V1' => $value_01,
            'V2' => $value_02,
        ];

        $this->field->setCriteriaValueFromXML(
            $this->criteria,
            $xml_criteria_value,
            $mapping
        );

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this->field));

        self::assertEquals(
            [$value_01],
            $cache->get($report_id)
        );
    }

    public function testItDoesNotSetCriteriaValueFromXMLIfNotAStaticBind(): void
    {
        $report_id = 'XML_IMPORT_' . bin2hex(random_bytes(32));
        $report    = ReportTestBuilder::aPublicReport()->withId($report_id)->build();
        $this->criteria->method('getReport')->willReturn($report);

        ListUserBindBuilder::aUserBind($this->field)->build();

        $xml_criteria_value = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
            <criteria_value type="list">
                <selected_value REF="V1"/>
            </criteria_value>
        ');

        $mapping = [];
        $this->field->setCriteriaValueFromXML(
            $this->criteria,
            $xml_criteria_value,
            $mapping
        );

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this->field));
        self::assertFalse($cache->has($report_id));
    }
}
