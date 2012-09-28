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

require_once dirname(__FILE__).'/../../builders/all.php';
require_once TRACKER_BASE_DIR.'/Tracker/FormElement/Tracker_FormElement_Field_List_Bind_Ugroups.class.php';

class Tracker_FormElement_Field_List_Bind_UgroupsExportToXmlTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->ugroup_manager = mock('UGroupManager');
        $this->value_dao      = mock('Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao');
        $this->field = aSelectBoxField()->build();
        $this->root        = new SimpleXMLElement('<bind type="ugroups" />');
        $this->xml_mapping = array();
        $this->field_id    = 12;

        $this->integrators_ugroup_name = 'integrators';
        $this->integrators_ugroup = new UGroup(array('name' => $this->integrators_ugroup_name));
        $this->integrators_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(345, $this->integrators_ugroup);

        $this->customers_ugroup_name = 'customers';
        $this->customers_ugroup = new UGroup(array('name' => $this->customers_ugroup_name));
        $this->customers_ugroup_value = new Tracker_FormElement_Field_List_Bind_UgroupsValue(687, $this->customers_ugroup);
    }

    public function itExportsEmptyUgroupList() {
        $bind_ugroup = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, array(), array(), array(), $this->ugroup_manager, $this->value_dao);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, $this->field_id);
        $this->assertCount($this->root->items->children(), 0);
    }

    public function itExportsOneUgroup() {
        $values = array(
            $this->integrators_ugroup_value
        );
        $bind_ugroup = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, array(), array(), $this->ugroup_manager, $this->value_dao);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, $this->field_id);
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['label'], $this->integrators_ugroup_name);
    }

    public function itExportsTwoUgroups() {
        $values = array(
            $this->integrators_ugroup_value,
            $this->customers_ugroup_value
        );
        $bind_ugroup = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, array(), array(), $this->ugroup_manager, $this->value_dao);

        $bind_ugroup->exportToXML($this->root, $this->xml_mapping, $this->field_id);
        $items = $this->root->items->children();
        $this->assertEqual($items[0]['label'], $this->integrators_ugroup_name);
        $this->assertEqual($items[1]['label'], $this->customers_ugroup_name);
    }
}


class Tracker_FormElement_Field_List_Bind_Ugroups_SaveObjectTest extends TuleapTestCase {
    public function setUp() {
        parent::setUp();

        $this->field_id = 789;
        $this->field = aSelectBoxField()->withId($this->field_id)->build();
        $this->ugroup_manager = mock('UGroupManager');
        $this->value_dao = mock('Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao');
    }

    public function itSavesNothingWhenNoValue() {
        $values = array();
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, array(), array(), $this->ugroup_manager, $this->value_dao);
        stub($this->value_dao)->create()->never();
        $bind->saveObject();
    }

    public function itSavesOneValue() {
        $ugroup_id = 103;
        $values = array(
            new Tracker_FormElement_Field_List_Bind_UgroupsValue(0, new UGroup(array('ugroup_id' => $ugroup_id, 'name' => 'whatever'))),
        );
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, array(), array(), $this->ugroup_manager, $this->value_dao);
        stub($this->value_dao)->create($this->field_id, $ugroup_id)->once();
        $bind->saveObject();
    }

    public function itSavesBothStaticAndDynamicValues() {
        $static_ugroup_id = 103;
        $values = array(
            new Tracker_FormElement_Field_List_Bind_UgroupsValue(0, new UGroup(array('ugroup_id' => UGROUP::PROJECT_MEMBERS, 'name' => 'whatever'))),
            new Tracker_FormElement_Field_List_Bind_UgroupsValue(0, new UGroup(array('ugroup_id' => $static_ugroup_id, 'name' => 'whatever'))),
        );
        $bind = new Tracker_FormElement_Field_List_Bind_Ugroups($this->field, $values, array(), array(), $this->ugroup_manager, $this->value_dao);
        stub($this->value_dao)->create($this->field_id, UGROUP::PROJECT_MEMBERS)->at(0);
        stub($this->value_dao)->create($this->field_id, $static_ugroup_id)->at(1);
        $bind->saveObject();
    }
}
?>
