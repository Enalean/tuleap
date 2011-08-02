<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
 
require_once(dirname(__FILE__).'/../include/Tracker_Artifact.class.php');
require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_List_BindFactory.class.php');
Mock::generatePartial(
    'Tracker_FormElement_Field_List_BindFactory', 
    'Tracker_FormElement_Field_List_BindFactoryTestVersion', 
    array(
        'getInstanceFromRow',
        'getStaticValueInstance',
        'getDecoratorInstance',
    )
);

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_List.class.php');
Mock::generate('Tracker_FormElement_Field_List');

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_List_BindValue.class.php');
Mock::generate('Tracker_FormElement_Field_List_BindValue');

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_List_BindDecorator.class.php');
Mock::generate('Tracker_FormElement_Field_List_BindDecorator');

class Tracker_FormElement_Field_List_BindFactoryTest extends UnitTestCase {
    
    public function testImport_statik() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="static" is_rank_alpha="1">
                <items>
                    <item ID="F6-V0" label="Open"/>
                    <item ID="F6-V1" label="Closed"/>
                </items>
                <decorators>
                    <decorator REF="F6-V0" r="255" g="0" b="0"/>
                    <decorator REF="F6-V1" r="0" g="255" b="0"/>
                </decorators>
                <default_values>
                    <value REF="F6-V0" />
                </default_values>
            </bind>'
        );
        
        $mapping = array();
        
        $v1 = new MockTracker_FormElement_Field_List_BindValue();
        $v2 = new MockTracker_FormElement_Field_List_BindValue();
        $d1 = new MockTracker_FormElement_Field_List_BindDecorator();
        $d2 = new MockTracker_FormElement_Field_List_BindDecorator();
        
        $field = new MockTracker_FormElement_Field_List();
        $bind  = new Tracker_FormElement_Field_List_BindFactoryTestVersion();
        $bind->expectAt(0, 'getStaticValueInstance', array('F6-V0', 'Open', '', 0, 0));
        $bind->expectAt(1, 'getStaticValueInstance', array('F6-V1', 'Closed', '', 1, 0));
        $bind->setReturnReference('getStaticValueInstance', $v1, array('F6-V0', 'Open', '', 0, 0));
        $bind->setReturnReference('getStaticValueInstance', $v2, array('F6-V1', 'Closed', '', 1, 0));
        $bind->setReturnReference('getDecoratorInstance', $d1, array($field, 'F6-V0', 255, 0, 0));
        $bind->setReturnReference('getDecoratorInstance', $d2, array($field, 'F6-V1', 0, 255, 0));
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
                    ),
                    'is_rank_alpha'  => 1,
                    'values'         => array(
                        'F6-V0' => $v1,
                        'F6-V1' => $v2,
                    ),
                )
            )
        );
        $bind->getInstanceFromXML($xml, $field, $mapping);
        $this->assertReference($mapping['F6-V0'], $v1);
        $this->assertReference($mapping['F6-V1'], $v2);
    }
    
    public function testImport_users() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="users">
                <items>
                    <item label="ugroup1"/>
                    <item label="ugroup2"/>
                </items>
            </bind>'
        );
        
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
        $bind->getInstanceFromXML($xml, $field, $mapping);
        $this->assertEqual($mapping, array());
    }
    
    public function testImport_unknown_type() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <bind type="unknown">
                <items>
                    <item ID="bla" label="blabla"/>
                </items>
            </bind>'
        );
        
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
                )
            )
        );
        $this->assertNull($bind->getInstanceFromXML($xml, $field, $mapping));
        $this->assertEqual($mapping, array());
    }

}
?>
