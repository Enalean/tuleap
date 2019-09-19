<?php
/**
 * Copyright (c) Enalean, 2012-2018. All Rights Reserved.
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
require_once('bootstrap.php');

Mock::generatePartial(
    'Tracker_FormElement_Field_List_BindFactory',
    'Tracker_FormElement_Field_List_BindFactoryTestVersion',
    array(
        'getInstanceFromRow',
        'getStaticValueInstance',
        'getDecoratorInstance',
    )
);

Mock::generate('Tracker_FormElement_Field_List');

Mock::generate('Tracker_FormElement_Field_List_BindValue');

Mock::generate('Tracker_FormElement_Field_List_BindDecorator');

class Tracker_FormElement_Field_List_BindFactoryTest extends TuleapTestCase
{

    public function testImport_statik()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="static" is_rank_alpha="1">
                <items>
                    <item ID="F6-V0" label="Open"/>
                    <item ID="F6-V1" label="Closed"/>
                    <item ID="F6-V2" label="On going"/>
                </items>
                <decorators>
                    <decorator REF="F6-V0" r="255" g="0" b="0"/>
                    <decorator REF="F6-V1" r="0" g="255" b="0"/>
                    <decorator REF="F6-V2" r="0" g="0" b="0" tlp_color_name="graffiti-yellow"/>
                </decorators>
                <default_values>
                    <value REF="F6-V0" />
                </default_values>
            </bind>');

        $mapping = array();

        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $v3 = new MockTracker_FormElement_Field_List_BindValue();
        $d1 = new MockTracker_FormElement_Field_List_BindDecorator();
        $d2 = new MockTracker_FormElement_Field_List_BindDecorator();
        $d3 = new MockTracker_FormElement_Field_List_BindDecorator();

        $field = new MockTracker_FormElement_Field_List();
        $bind  = new Tracker_FormElement_Field_List_BindFactoryTestVersion();
        $bind->expectAt(0, 'getStaticValueInstance', array('F6-V0', 'Open', '', 0, 0));
        $bind->expectAt(1, 'getStaticValueInstance', array('F6-V1', 'Closed', '', 1, 0));
        $bind->expectAt(2, 'getStaticValueInstance', array('F6-V2', 'On going', '', 2, 0));
        $bind->setReturnReference('getStaticValueInstance', $v1, array('F6-V0', 'Open', '', 0, 0));
        $bind->setReturnReference('getStaticValueInstance', $v2, array('F6-V1', 'Closed', '', 1, 0));
        $bind->setReturnReference('getStaticValueInstance', $v3, array('F6-V2', 'On going', '', 2, 0));
        $bind->setReturnReference('getDecoratorInstance', $d1, array($field, 'F6-V0', 255, 0, 0, ''));
        $bind->setReturnReference('getDecoratorInstance', $d2, array($field, 'F6-V1', 0, 255, 0, ''));
        $bind->setReturnReference('getDecoratorInstance', $d3, array($field, 'F6-V2', 0, 0, 0, 'graffiti-yellow'));
        $bind->expect(
            'getInstanceFromRow',
            array(
                array(
                    'type'           => 'static',
                    'field'          => $field,
                    'default_values' => array(
                        'F6-V0' => $v1,
                    ),
                    'decorators'     => array(
                        'F6-V0' => $d1,
                        'F6-V1' => $d2,
                        'F6-V2' => $d3,
                    ),
                    'is_rank_alpha'  => 1,
                    'values'         => array(
                        'F6-V0' => $v1,
                        'F6-V1' => $v2,
                        'F6-V2' => $v3,
                    ),
                )
            )
        );
        $bind->getInstanceFromXML($xml, $field, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'));
        $this->assertReference($mapping['F6-V0'], $v1);
        $this->assertReference($mapping['F6-V1'], $v2);
        $this->assertReference($mapping['F6-V2'], $v3);
    }

    public function testImport_users()
    {

        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="users">
                <items>
                    <item label="ugroup1"/>
                    <item label="ugroup2"/>
                </items>
            </bind>');

        $mapping = array();

        $field = new MockTracker_FormElement_Field_List();
        $bind  = new Tracker_FormElement_Field_List_BindFactoryTestVersion();
        $bind->expect(
            'getInstanceFromRow',
            array(
                array(
                    'type'           => 'users',
                    'field'          => $field,
                    'default_values' => null,
                    'decorators'     => null,
                    'value_function' => 'ugroup1,ugroup2',
                )
            )
        );
        $bind->getInstanceFromXML($xml, $field, $mapping, mock('User\XML\Import\IFindUserFromXMLReference'));
        $this->assertEqual($mapping, array());
    }

    public function testImport_unknown_type()
    {

        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="unknown">
                <items>
                    <item ID="bla" label="blabla"/>
                </items>
            </bind>');

        $mapping = array();

        $field = new MockTracker_FormElement_Field_List();
        $bind  = new Tracker_FormElement_Field_List_BindFactoryTestVersion();
        $bind->expect(
            'getInstanceFromRow',
            array(
                array(
                    'type'           => 'unknown',
                    'field'          => $field,
                    'default_values' => array(),
                    'decorators'     => null,
                )
            )
        );
        $this->assertEqual($mapping, array());
    }

    function itRaisesAnErrorIfUnkownType()
    {
        $factory = new Tracker_FormElement_Field_List_BindFactory(mock('UGroupManager'));
        $this->expectError('Unknown bind "unknown"');
        $factory->getInstanceFromRow(array('type' => 'unknown', 'field' => 'a_field_object'));
    }
}

class Tracker_FormElement_Field_List_BindFactoryImportUGroupsTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->mapping        = array();
        $this->project        = mock('Project');
        $this->field          = aSelectBoxField()->withTracker(aTracker()->withProject($this->project)->build())->build();
        $this->ugroup_manager = mock('UGroupManager');
        $this->value_dao      = mock('Tracker_FormElement_Field_List_Bind_Ugroups_ValueDao');
        $this->user_finder    = mock('User\XML\Import\IFindUserFromXMLReference');
        $this->bind_factory   = new Tracker_FormElement_Field_List_BindFactory($this->ugroup_manager);
        $this->bind_factory->setUgroupsValueDao($this->value_dao);

        $this->setText('Registered users', array('project_ugroup', 'ugroup_registered_users'));
    }

    public function itImportsStaticUgroups()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="Integrators" is_hidden="0" />
                    <item ID="F1-V1" label="Customers" is_hidden="0" />
                </items>
            </bind>');

        stub($this->ugroup_manager)->getUGroupByName($this->project, 'Integrators')->returns(new ProjectUGroup(array('name' => 'Integrators')));
        stub($this->ugroup_manager)->getUGroupByName($this->project, 'Customers')->returns(new ProjectUGroup(array('name' => 'Customers')));

        $bind = $this->bind_factory->getInstanceFromXML($xml, $this->field, $this->mapping, $this->user_finder);

        $values = $bind->getAllValues();
        $this->assertEqual($values["F1-V0"]->getLabel(), 'Integrators');
        $this->assertEqual($values["F1-V1"]->getLabel(), 'Customers');
    }

    public function itImportsIgnoresStaticUgroupThatDoesntBelongToProject()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="NotInProject" is_hidden="0" />
                </items>
            </bind>');

        stub($this->ugroup_manager)->getUGroupByName($this->project, 'NotInProject')->returns(false);

        $bind = $this->bind_factory->getInstanceFromXML($xml, $this->field, $this->mapping, $this->user_finder);

        $this->assertCount($bind->getAllValues(), 0);
    }

    public function itImportsDynamicUgroups()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="0" />
                </items>
            </bind>');

        stub($this->ugroup_manager)->getUGroupByName($this->project, 'ugroup_registered_users_name_key')->returns(new ProjectUGroup(array('name' => 'ugroup_registered_users_name_key')));

        $bind = $this->bind_factory->getInstanceFromXML($xml, $this->field, $this->mapping, $this->user_finder);

        $values = $bind->getAllValues();
        $this->assertEqual($values["F1-V0"]->getLabel(), 'Registered users');
    }

    public function itImportsHiddenValues()
    {
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="ugroups">
                <items>
                    <item ID="F1-V0" label="ugroup_registered_users_name_key" is_hidden="1" />
                </items>
            </bind>');

        stub($this->ugroup_manager)->getUGroupByName($this->project, 'ugroup_registered_users_name_key')->returns(new ProjectUGroup(array('name' => 'ugroup_registered_users_name_key')));

        $bind = $this->bind_factory->getInstanceFromXML($xml, $this->field, $this->mapping, $this->user_finder);

        $values = $bind->getAllValues();
        $this->assertTrue($values["F1-V0"]->isHidden());
    }
}
