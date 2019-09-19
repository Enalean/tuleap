<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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
require_once('bootstrap.php');

class Tracker_FormElement_Field_List_Bind_BaseTest extends TuleapTestCase
{
    public function setUp()
    {
        parent::setUp();
        $this->field_id          = 12;
        $this->ugroup_manager    = mock('UGroupManager');
        $this->value_dao         = mock('Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao');
        $this->default_value_dao = mock('Tracker_FormElement_Field_List_Bind_DefaultvalueDao');
        $this->field             = aSelectBoxField()->withId($this->field_id)->build();
        $this->root              = new SimpleXMLElement('<bind type="ugroups" />');
        $this->xml_mapping       = array();

        $this->integrators_ugroup_id    = 103;
        $this->integrators_ugroup_name  = 'Integrators';
        $this->integrators_ugroup       = new ProjectUGroup(array('ugroup_id' => $this->integrators_ugroup_id, 'name' => $this->integrators_ugroup_name));
        $this->integrators_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(345, $this->integrators_ugroup, false);

        $this->customers_ugroup_id    = 104;
        $this->customers_ugroup_name  = 'Customers';
        $this->customers_ugroup       = new ProjectUGroup(array('ugroup_id' => $this->customers_ugroup_id, 'name' => $this->customers_ugroup_name));
        $this->customers_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(687, $this->customers_ugroup, false);

        $this->project_members_ugroup_name  = 'ugroup_project_members_name_key';
        $this->project_members_ugroup       = new ProjectUGroup(array('ugroup_id' => ProjectUGroup::PROJECT_MEMBERS, 'name' => $this->project_members_ugroup_name));
        $this->project_members_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(4545, $this->project_members_ugroup, false);

        $this->hidden_ugroup_id    = 105;
        $this->hidden_ugroup_name  = 'Unused ProjectUGroup';
        $this->hidden_ugroup       = new ProjectUGroup(array('ugroup_id' => $this->hidden_ugroup_id, 'name' => $this->hidden_ugroup_name));
        $this->hidden_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(666, $this->hidden_ugroup, true);
    }

    protected function buildBindUgroups(array $values = array(), array $default_values = array())
    {
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, $default_values, array(), $this->ugroup_manager, $this->value_dao);
        $bind->setDefaultValueDao($this->default_value_dao);
        return $bind;
    }
}

class Tracker_FormElement_Field_List_Bind_UgroupsExportToXmlTest extends Tracker_FormElement_Field_List_Bind_BaseTest
{

    public function itExportsEmptyUgroupList()
    {
        $bind_ugroup = $this->buildBindUgroups();

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $this->assertCount($this->root->items->children(), 0);
    }

    public function itExportsOneUgroup()
    {
        $values = array(
            $this->integrators_ugroup_value
        );
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['label'], $this->integrators_ugroup_name);
    }

    public function itExportsHiddenValues()
    {
        $values = array(
            $this->hidden_ugroup_value
        );
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['is_hidden'], true);
    }

    public function itExportsOneDynamicUgroup()
    {
        $values = array(
            $this->project_members_ugroup_value
        );
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['label'], $this->project_members_ugroup_name);
    }

    public function itExportsTwoUgroups()
    {
        $values = array(
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value
        );
        $bind_ugroup = $this->buildBindUgroups($values);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['label'], $this->integrators_ugroup_name);
        $this->assertEqual($items[1]['label'], $this->customers_ugroup_name);
    }

    public function itExportsDefaultValues()
    {
        $values = array(
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value
        );
        $default_values = array(
            "{$this->customers_ugroup_value->getId()}" => true,
        );
        $bind_ugroup = $this->buildBindUgroups($values, $default_values);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, false, mock('UserXmlExporter'));
        $items = $this->root->default_values->children();
        $this->assertEqual((string)$items->value['REF'], 'V687');
    }
}

class Tracker_FormElement_Field_List_Bind_Ugroups_SaveObjectTest extends Tracker_FormElement_Field_List_Bind_BaseTest
{

    public function itSavesNothingWhenNoValue()
    {
        $values = array();
        $bind = $this->buildBindUgroups($values);
        stub($this->value_dao)->create()->never();
        $bind->saveObject();
    }

    public function itSavesOneValue()
    {
        $values = array(
            $this->customers_ugroup_value,
        );
        $bind = $this->buildBindUgroups($values);
        stub($this->value_dao)->create($this->field_id, $this->customers_ugroup_id, false)->once();
        $bind->saveObject();
    }

    public function itSavesBothStaticAndDynamicValues()
    {
        $values = array(
            $this->project_members_ugroup_value,
            $this->customers_ugroup_value,
        );
        $bind = $this->buildBindUgroups($values);
        stub($this->value_dao)->create($this->field_id, ProjectUGroup::PROJECT_MEMBERS, false)->at(0);
        stub($this->value_dao)->create($this->field_id, $this->customers_ugroup_id, false)->at(1);
        $bind->saveObject();
    }

    public function itSavesTheHiddenState()
    {
        $values = array(
            $this->hidden_ugroup_value,
        );
        $bind = $this->buildBindUgroups($values);
        stub($this->value_dao)->create($this->field_id, $this->hidden_ugroup_id, true)->at(1);
        $bind->saveObject();
    }

    public function itSetsTheNewIdOfTheValueSoThatDefaultValuesAreProperlySaved()
    {
        $this->integrators_ugroup_value->setId('F1-V23 (from xml structure)');
        $values = array(
            $this->integrators_ugroup_value,
        );
        $bind = $this->buildBindUgroups($values);
        stub($this->value_dao)->create()->returns('new id');
        $bind->saveObject();
        $this->assertEqual($this->integrators_ugroup_value->getId(), 'new id');
    }
}

class Tracker_FormElement_Field_List_Bind_Ugroups_CreateUpdateValuesTest extends Tracker_FormElement_Field_List_Bind_BaseTest
{

    public function setUp()
    {
        parent::setUp();
        $values = array(
            1 => $this->project_members_ugroup_value,
            2 => $this->customers_ugroup_value,
            3 => $this->hidden_ugroup_value,
        );
        $this->bind   = $this->buildBindUgroups($values);
        $this->params = array(
            'values' => array(
                ProjectUGroup::PROJECT_MEMBERS,
                $this->integrators_ugroup_id,
                $this->hidden_ugroup_id,
            )
        );
    }

    public function itActivatesAlreadyExistingHiddenValues()
    {
        stub($this->value_dao)->show($this->hidden_ugroup_value->getId())->once();
    }

    public function itCreatesNewValue()
    {
        stub($this->value_dao)->create($this->field_id, $this->integrators_ugroup_id, false)->once();
    }

    public function itHidesPreviouslyUsedValue()
    {
        stub($this->value_dao)->hide($this->customers_ugroup_value->getId())->once();
    }

    public function tearDown()
    {
        $this->bind->process($this->params, true, false);
        parent::tearDown();
    }
}

/*class Tracker_FormElement_Field_List_Bind_Ugroups_CSVAndWebImportTest extends TuleapTestCase {
    function testGetFieldData() {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv1->setReturnValue('getUsername', 'john.smith');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv2->setReturnValue('getUsername', 'sam.anderson');
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(108 => $bv1, 110 => $bv2);
        $f = new Tracker_FormElement_Field_List_Bind_UsersTestVersion($field, $is_rank_alpha, $values, $default_values, $decorators);
        $f->setReturnReference('getAllValues', $values);
        $this->assertEqual('108', $f->getFieldData('john.smith', false));
    }

    function testGetFieldDataMultiple() {
        $bv1 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv1->setReturnValue('getUsername', 'john.smith');
        $bv2 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv2->setReturnValue('getUsername', 'sam.anderson');
        $bv3 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv3->setReturnValue('getUsername', 'tom.brown');
        $bv4 = new MockTracker_FormElement_Field_List_Bind_UsersValue();
        $bv4->setReturnValue('getUsername', 'patty.smith');
        $field = $is_rank_alpha = $default_values = $decorators = '';
        $values = array(108 => $bv1, 110 => $bv2, 113 => $bv3, 115 => $bv4);
        $f = new Tracker_FormElement_Field_List_Bind_UsersTestVersion($field, $is_rank_alpha, $values, $default_values, $decorators);
        $f->setReturnReference('getAllValues', $values);
        $res = array(108,113);
        $this->assertEqual($res, $f->getFieldData('john.smith,tom.brown', true));
    }
}*/
