<?php
/**
 * Copyright (c) Enalean, 2012-present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\Tracker\FormElement;

use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use ProjectUGroup;
use SimpleXMLElement;
use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_UgroupsValue;
use Tuleap\DB\DatabaseUUIDV7Factory;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;
use Tuleap\Tracker\Test\Builders\Fields\List\ListUserGroupValueBuilder;
use Tuleap\Tracker\Test\Builders\Fields\ListFieldBuilder;
use UGroupManager;
use UserXMLExporter;

#[DisableReturnValueGenerationForTestDoubles]
final class Tracker_FormElement_Field_List_Bind_UgroupsTest extends TestCase //phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker_FormElement_Field_List_Bind_UgroupsValue $customers_ugroup_value;
    private Tracker_FormElement_Field_List_Bind_UgroupsValue $project_members_ugroup_value;
    private Tracker_FormElement_Field_List_Bind_UgroupsValue $hidden_ugroup_value;
    private Tracker_FormElement_Field_List_Bind_UgroupsValue $integrators_ugroup_value;
    private BindDefaultValueDao&MockObject $default_value_dao;
    private BindUgroupsValueDao&MockObject $value_dao;
    private UGroupManager&MockObject $ugroup_manager;
    private Tracker_FormElement_Field_List $field;

    protected function setUp(): void
    {
        $this->ugroup_manager    = $this->createMock(UGroupManager::class);
        $this->value_dao         = $this->createMock(BindUgroupsValueDao::class);
        $this->default_value_dao = $this->createMock(BindDefaultValueDao::class);
        $this->field             = ListFieldBuilder::aListField(10)->build();
        $uuid_factory            = new DatabaseUUIDV7Factory();

        $integrators_ugroup_id          = 103;
        $integrators_ugroup_name        = 'Integrators';
        $integrators_ugroup             = new ProjectUGroup(
            ['ugroup_id' => $integrators_ugroup_id, 'name' => $integrators_ugroup_name]
        );
        $this->integrators_ugroup_value = ListUserGroupValueBuilder::aUserGroupValue($integrators_ugroup)->withId(345)->withUUId($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()))->build();

        $customers_ugroup_id          = 104;
        $customers_ugroup_name        = 'Customers';
        $customers_ugroup             = new ProjectUGroup(
            ['ugroup_id' => $customers_ugroup_id, 'name' => $customers_ugroup_name]
        );
        $this->customers_ugroup_value = ListUserGroupValueBuilder::aUserGroupValue($customers_ugroup)->withId(687)->withUUId($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()))->build();

        $project_members_ugroup_name        = 'ugroup_project_members_name_key';
        $project_members_ugroup             = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'name' => $project_members_ugroup_name]
        );
        $this->project_members_ugroup_value = ListUserGroupValueBuilder::aUserGroupValue($project_members_ugroup)->withId(4545)->withUUId($uuid_factory->buildUUIDFromBytesData($uuid_factory->buildUUIDBytes()))->build();

        $hidden_ugroup_id          = 105;
        $hidden_ugroup_name        = 'Unused ProjectUGroup';
        $hidden_ugroup             = new ProjectUGroup(
            ['ugroup_id' => $hidden_ugroup_id, 'name' => $hidden_ugroup_name]
        );
        $this->hidden_ugroup_value = ListUserGroupValueBuilder::aUserGroupValue($hidden_ugroup)->withId(666)->isHidden(true)->build();
    }

    private function buildBindUgroups(
        array $values = [],
        array $default_values = [],
    ): Tracker_FormElement_Field_List_Bind_Ugroups {
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups(
            new DatabaseUUIDV7Factory(),
            $this->field,
            $values,
            $default_values,
            [],
            $this->ugroup_manager,
            $this->value_dao
        );
        $bind->setDefaultValueDao($this->default_value_dao);

        return $bind;
    }

    public function testItExportsEmptyUgroupList(): void
    {
        $bind_ugroup = $this->buildBindUgroups();
        $xml_mapping = [];

        $root = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        self::assertCount(0, $root->items->children());
    }

    public function testItExportsOneUgroup(): void
    {
        $xml_mapping = [];
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $values      = [
            $this->integrators_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        $items = $root->items->children();
        self::assertEquals('Integrators', $items[0]['label']);
    }

    public function testItExportsHiddenValues(): void
    {
        $xml_mapping = [];
        $values      = [
            $this->hidden_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        $items = $root->items->children();
        self::assertTrue((bool) $items[0]['is_hidden']);
    }

    public function testItExportsOneDynamicUgroup(): void
    {
        $xml_mapping = [];
        $values      = [
            $this->project_members_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        $items = $root->items->children();
        self::assertEquals('ugroup_project_members_name_key', $items[0]['label']);
    }

    public function testItExportsTwoUgroups(): void
    {
        $xml_mapping = [];
        $values      = [
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        $items = $root->items->children();
        self::assertEquals('Integrators', $items[0]['label']);
        self::assertEquals('Customers', $items[1]['label']);
    }

    public function testItExportsDefaultValues(): void
    {
        $xml_mapping    = [];
        $values         = [
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value,
        ];
        $default_values = [
            "{$this->customers_ugroup_value->getId()}" => true,
        ];
        $bind_ugroup    = $this->buildBindUgroups($values, $default_values);
        $root           = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportBindToXml($root, $xml_mapping, false, $this->createStub(UserXMLExporter::class));
        $items = $root->default_values->children();
        self::assertEquals($this->customers_ugroup_value->getUuid(), (string) $items->value['REF']);
    }

    public function testItSavesNothingWhenNoValue(): void
    {
        $values = [];
        $bind   = $this->buildBindUgroups($values);
        $this->value_dao->expects($this->never())->method('create');
        $bind->saveObject();
    }

    public function testItSavesOneValue(): void
    {
        $values = [
            $this->customers_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);

        $this->value_dao->expects($this->once())->method('create')->with($this->field->getId(), $this->customers_ugroup_value->getUgroupId(), false);
        $bind->saveObject();
    }

    public function testItSavesBothStaticAndDynamicValues(): void
    {
        $values = [
            $this->project_members_ugroup_value,
            $this->customers_ugroup_value,
        ];

        $bind = $this->buildBindUgroups($values);
        $this->value_dao->expects($this->atLeast(2))->method('create')
            ->with(
                $this->field->getId(),
                self::callback(fn(int $ugroup_id) => $ugroup_id === ProjectUGroup::PROJECT_MEMBERS || $ugroup_id === $this->customers_ugroup_value->getUgroupId()),
                false,
            );
        $bind->saveObject();
    }

    public function testItSavesTheHiddenState(): void
    {
        $values = [
            $this->hidden_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);

        $this->value_dao->expects($this->atLeastOnce())->method('create')
            ->with($this->field->getId(), $this->hidden_ugroup_value->getUgroupId(), true);
        $bind->saveObject();
    }

    public function testItSetsTheNewIdOfTheValueSoThatDefaultValuesAreProperlySaved(): void
    {
        $this->integrators_ugroup_value->setId('F1-V23 (from xml structure)');
        $values = [
            $this->integrators_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);
        $this->value_dao->method('create')->willReturn(11);
        $bind->saveObject();
        self::assertEquals(11, $this->integrators_ugroup_value->getId());
    }
}
