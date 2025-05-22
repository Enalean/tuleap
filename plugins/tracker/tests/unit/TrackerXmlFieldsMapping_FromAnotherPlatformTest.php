<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

use Tuleap\Test\Builders\ProjectUGroupTestBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListStaticValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerXmlFieldsMapping_FromAnotherPlatformTest extends \Monolog\Test\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private TrackerXmlFieldsMapping_FromAnotherPlatform $xml_open_fields_mapping;

    private TrackerXmlFieldsMapping_FromAnotherPlatform $xml_ugroup_fields_mapping;

    private TrackerXmlFieldsMapping_FromAnotherPlatform $xml_static_fields_mapping;

    protected function setUp(): void
    {
        $static_value_01 = $this->getBindValueWithId(24076);
        $static_value_02 = $this->getBindValueWithId(24077);
        $static_value_03 = $this->getBindValueWithId(24078);
        $static_value_04 = $this->getBindValueWithId(24079);
        $static_value_05 = $this->getBindValueWithId(24080);
        $static_value_06 = $this->getBindValueWithId(24081);

        $list_field = ListFieldBuilder::aListField(101)->build();

        $xml_static_mapping = [
            'F21840' => $list_field,
            'V24058' => $static_value_01,
            'V24059' => $static_value_02,
            'V24060' => $static_value_03,
            'V24061' => $static_value_04,
            'V24062' => $static_value_05,
            'V24063' => $static_value_06,
        ];

        $this->xml_static_fields_mapping = new TrackerXmlFieldsMapping_FromAnotherPlatform($xml_static_mapping);

        $ugroup_value_01 = $this->getBindForUGroupWithId(300);
        $ugroup_value_02 = $this->getBindForUGroupWithId(301);
        $ugroup_value_03 = $this->getBindForUGroupWithId(302);

        $xml_ugroup_mapping = [
            'F21840' => $list_field,
            'V198'   => $ugroup_value_01,
            'V200'   => $ugroup_value_02,
            'V201'   => $ugroup_value_03,
        ];

        $this->xml_ugroup_fields_mapping = new TrackerXmlFieldsMapping_FromAnotherPlatform($xml_ugroup_mapping);

        $static_value_01 = ListStaticValueBuilder::aStaticValue('01')->withId(24076)->build();
        $static_value_02 = ListStaticValueBuilder::aStaticValue('02')->withId(24077)->build();
        $static_value_03 = ListStaticValueBuilder::aStaticValue('03')->withXMLId('bug_label')->build();

        $open_list_field  = Mockery::mock(Tracker_FormElement_Field_OpenList::class);
        $open_xml_mapping = [
            'F21840'     => $open_list_field,
            'V24058'     => $static_value_01,
            'V24059'     => $static_value_02,
            'Vbug_label' => $static_value_03,
        ];

        $this->xml_open_fields_mapping = new TrackerXmlFieldsMapping_FromAnotherPlatform($open_xml_mapping);
    }

    public function testItGetsNewValueIdForAStaticList(): void
    {
        $new_value_id = $this->xml_static_fields_mapping->getNewValueId(24058);

        $this->assertEquals(24076, $new_value_id);
    }

    public function testItThrowsAnExceptionIfTheNewValueIsNotFound(): void
    {
        $this->expectException(\TrackerXmlFieldsMapping_ValueNotFoundException::class);

        $this->xml_static_fields_mapping->getNewValueId(12345);
    }

    private function getBindValueWithId(int $bind_value_id): \Tracker_FormElement_Field_List_Bind_StaticValue
    {
        return ListStaticValueBuilder::aStaticValue('static')->withId($bind_value_id)->build();
    }

    protected function getBindForUGroupWithId(int $ugroup_id): Tracker_FormElement_Field_List_Bind_UgroupsValue
    {
        return ListUserGroupValueBuilder::aUserGroupValue(ProjectUGroupTestBuilder::aCustomUserGroup(123)->build())->withId($ugroup_id)->build();
    }

    public function testItGetsNewValueIdForAUGroupList(): void
    {
        $new_value_id = $this->xml_ugroup_fields_mapping->getNewValueId(200);

        $this->assertEquals(301, $new_value_id);
    }

    public function testItThrowsAnExceptionIfTheNewValueForUgroupIsNotFound(): void
    {
        $this->expectException(\TrackerXmlFieldsMapping_ValueNotFoundException::class);

        $this->xml_ugroup_fields_mapping->getNewValueId(12345);
    }

    public function testItGetsNewValueIdForAOpenStaticList(): void
    {
        $new_value_id = $this->xml_open_fields_mapping->getNewOpenValueId('b24058');

        self::assertEquals('24076', $new_value_id);

        $new_value_id = $this->xml_open_fields_mapping->getNewOpenValueId('bbug_label');

        self::assertEquals('bug_label', $new_value_id);
    }

    public function testItThrowsAnExceptionIfTheNewValueOfOpenStatisIsNotFound(): void
    {
        $this->expectException(\TrackerXmlFieldsMapping_ValueNotFoundException::class);

        $this->xml_open_fields_mapping->getNewValueId('12345');
    }
}
