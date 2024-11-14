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

use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindDefaultValueDao;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;

final class Tracker_FormElement_Field_List_Bind_UgroupsTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue
     */
    private $customers_ugroup_value;
    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue
     */
    private $project_members_ugroup_value;
    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue
     */
    private $hidden_ugroup_value;

    /**
     * @var Tracker_FormElement_Field_List_Bind_UgroupsValue
     */
    private $integrators_ugroup_value;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BindDefaultValueDao
     */
    private $default_value_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|BindUgroupsValueDao
     */
    private $value_dao;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|Tracker_FormElement_Field_Selectbox
     */
    private $field;

    protected function setUp(): void
    {
        $this->ugroup_manager    = Mockery::mock(UGroupManager::class);
        $this->value_dao         = Mockery::mock(BindUgroupsValueDao::class);
        $this->default_value_dao = Mockery::mock(BindDefaultValueDao::class);
        $this->field             = Mockery::mock(Tracker_FormElement_Field_Selectbox::class);

        $integrators_ugroup_id          = 103;
        $integrators_ugroup_name        = 'Integrators';
        $integrators_ugroup             = new ProjectUGroup(
            ['ugroup_id' => $integrators_ugroup_id, 'name' => $integrators_ugroup_name]
        );
        $this->integrators_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(
            345,
            $integrators_ugroup,
            false
        );

        $customers_ugroup_id          = 104;
        $customers_ugroup_name        = 'Customers';
        $customers_ugroup             = new ProjectUGroup(
            ['ugroup_id' => $customers_ugroup_id, 'name' => $customers_ugroup_name]
        );
        $this->customers_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(
            687,
            $customers_ugroup,
            false
        );

        $project_members_ugroup_name        = 'ugroup_project_members_name_key';
        $project_members_ugroup             = new ProjectUGroup(
            ['ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'name' => $project_members_ugroup_name]
        );
        $this->project_members_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(
            4545,
            $project_members_ugroup,
            false
        );
        $hidden_ugroup_id                   = 105;
        $hidden_ugroup_name                 = 'Unused ProjectUGroup';
        $hidden_ugroup                      = new ProjectUGroup(
            ['ugroup_id' => $hidden_ugroup_id, 'name' => $hidden_ugroup_name]
        );
        $this->hidden_ugroup_value          = new Tracker_FormElement_Field_List_Bind_UgroupsValue(
            666,
            $hidden_ugroup,
            true
        );
    }

    private function buildBindUgroups(
        array $values = [],
        array $default_values = [],
    ): Tracker_FormElement_Field_List_Bind_Ugroups {
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups(
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

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $this->assertCount(0, $root->items->children());
    }

    public function testItExportsOneUgroup(): void
    {
        $xml_mapping = [];
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $values      = [
            $this->integrators_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $items = $root->items->children();
        $this->assertEquals('Integrators', $items[0]['label']);
    }

    public function testItExportsHiddenValues()
    {
        $values      = [
            $this->hidden_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $items = $root->items->children();
        $this->assertTrue((bool) $items[0]['is_hidden']);
    }

    public function testItExportsOneDynamicUgroup(): void
    {
        $values      = [
            $this->project_members_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $items = $root->items->children();
        $this->assertEquals('ugroup_project_members_name_key', $items[0]['label']);
    }

    public function testItExportsTwoUgroups(): void
    {
        $values      = [
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value,
        ];
        $bind_ugroup = $this->buildBindUgroups($values);
        $root        = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $items = $root->items->children();
        $this->assertEquals('Integrators', $items[0]['label']);
        $this->assertEquals('Customers', $items[1]['label']);
    }

    public function testItExportsDefaultValues(): void
    {
        $values         = [
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value,
        ];
        $default_values = [
            "{$this->customers_ugroup_value->getId()}" => true,
        ];
        $bind_ugroup    = $this->buildBindUgroups($values, $default_values);
        $root           = new SimpleXMLElement('<bind type="ugroups" />');

        $bind_ugroup->exportToXML($root, $xml_mapping, false, Mockery::mock(UserXMLExporter::class));
        $items = $root->default_values->children();
        $this->assertEquals('V687', (string) $items->value['REF']);
    }

    public function testItSavesNothingWhenNoValue(): void
    {
        $values = [];
        $bind   = $this->buildBindUgroups($values);
        $this->value_dao->shouldReceive('create')->never();
        $bind->saveObject();
    }

    public function testItSavesOneValue(): void
    {
        $values = [
            $this->customers_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);

        $this->field->shouldReceive('getId')->andReturn(10);

        $this->value_dao->shouldReceive('create')->withArgs(
            [$this->field->getId(), $this->customers_ugroup_value->getUgroupId(), false]
        )->once();
        $bind->saveObject();
    }

    public function testItSavesBothStaticAndDynamicValues(): void
    {
        $values = [
            $this->project_members_ugroup_value,
            $this->customers_ugroup_value,
        ];
        $this->field->shouldReceive('getId')->andReturn(10);

        $bind = $this->buildBindUgroups($values);
        $this->value_dao->shouldReceive('create')->withArgs(
            [$this->field->getId(), ProjectUGroup::PROJECT_MEMBERS, false]
        )->atLeast()->once();
        $this->value_dao->shouldReceive('create')->withArgs(
            [$this->field->getId(), $this->customers_ugroup_value->getUgroupId(), false]
        )->atLeast()->once();
        $bind->saveObject();
    }

    public function testItSavesTheHiddenState(): void
    {
        $values = [
            $this->hidden_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);
        $this->field->shouldReceive('getId')->andReturn(10);

        $this->value_dao->shouldReceive('create')->withArgs(
            [$this->field->getId(), $this->hidden_ugroup_value->getUgroupId(), true]
        )->atLeast()->once();
        $bind->saveObject();
    }

    public function testItSetsTheNewIdOfTheValueSoThatDefaultValuesAreProperlySaved(): void
    {
        $this->integrators_ugroup_value->setId('F1-V23 (from xml structure)');
        $values = [
            $this->integrators_ugroup_value,
        ];
        $bind   = $this->buildBindUgroups($values);
        $this->field->shouldReceive('getId')->andReturn(10);
        $this->value_dao->shouldReceive('create')->andReturn(11);
        $bind->saveObject();
        $this->assertEquals(11, $this->integrators_ugroup_value->getId());
    }
}
