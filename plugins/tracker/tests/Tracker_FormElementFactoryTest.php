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

require_once(dirname(__FILE__).'/../include/Tracker_FormElementFactory.class.php');
Mock::generatePartial(
    'Tracker_FormElementFactory', 
    'Tracker_FormElementFactoryTestVersion', 
    array(
        'getInstanceFromRow',
        'getFormElementById',
        'createFormElement',        
    )
);

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Container_Fieldset.class.php');
Mock::generate('Tracker_FormElement_Container_Fieldset');

require_once(dirname(__FILE__).'/../include/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');

require_once(dirname(__FILE__).'/../include/Tracker.class.php');
Mock::generate('Tracker');


class Tracker_FormElementFactoryTest extends UnitTestCase {

    
    public function test_saveObject() {
        $tracker       = new MockTracker();
        
        $a_formelement = new MockTracker_FormElement_Container_Fieldset();
        
        $a_formelement->expect('setId', array(66));
        $a_formelement->expectOnce('afterSaveObject');
        $a_formelement->setReturnValue('getFlattenPropertiesValues', array());
        
        $tff = new Tracker_FormElementFactoryTestVersion();
        $tff->setReturnValue('createFormElement', 66);
        
        $this->assertEqual($tff->saveObject($tracker, $a_formelement, 0), 66);
    }
    
    public function testImportFormElement() {
        
        $xml = new SimpleXMLElement('<?xml version="1.0" standalone="yes"?>
            <formElement type="mon_type" ID="F0" rank="20" required="1" notifications="1">
                <name>field_name</name>
                <label>field_label</label>
                <description>field_description</description>
            </formElement>'
        );
        
        $mapping = array();
        
        $a_formelement = new MockTracker_FormElement_Container_Fieldset();
        $a_formelement->expect('continueGetInstanceFromXML', array(
            $xml,
            '*' //$mapping
        ));
        
        $tf = new Tracker_FormElementFactoryTestVersion();
        $tf->setReturnReference(
            'getInstanceFromRow', 
            $a_formelement, 
            array(
                array(
                    'formElement_type' => 'mon_type',
                    'name'             => 'field_name',
                    'label'            => 'field_label',
                    'rank'             => 20,
                    'use_it'           => 1,   //default
                    'scope'            => 'P', //default
                    'required'         => 1,
                    'notifications'    => 1,
                    'description'      => 'field_description',
                    'id'               => 0,
                    'tracker_id'       => 0,
                    'parent_id'        => 0,
                )
            )
        );
        
        $f = $tf->getInstanceFromXML($xml, $mapping);
        
        $this->assertReference($f, $a_formelement);
        $this->assertReference($mapping['F0'], $a_formelement);
    }
    //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)
    function test_getPermissionFromFormElementData() {
      $formElementData = array('permissions'=> array( 
                                               $GLOBALS['UGROUP_ANONYMOUS'] => array(0 => 'PLUGIN_TRACKER_FIELD_READ',
                                                                            1 => 'PLUGIN_TRACKER_FIELD_UPDATE'), 
                                               $GLOBALS['UGROUP_REGISTERED'] => array(0 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                                                                            1 => 'PLUGIN_TRACKER_FIELD_READ'),                                                                                              
          ) );

      $ff = Tracker_FormElementFactory::instance();
      $elmtId = 134;
      
      $ugroups_permissions = $ff->getPermissionsFromFormElementData($elmtId, $formElementData);     
      $this->assertTrue(isset($ugroups_permissions[$elmtId]));
      $this->assertTrue(isset($ugroups_permissions[$elmtId][1]));//ugroup_anonymous
      $this->assertTrue(isset($ugroups_permissions[$elmtId][2]));//ugroup_registered
      $this->assertTrue(isset($ugroups_permissions[$elmtId][1]['others']));
      $this->assertEqual($ugroups_permissions[$elmtId][1]['others'], 1);
      $this->assertEqual($ugroups_permissions[$elmtId][2]['others'], 0);
      //$this->assertEqual(isset($ugroups_permissions[$elmtId][2]['submit']));
      //$this->assertEqual($ugroups_permissions[$elmtId][2]['submit'], 'on');      
    }

    function test_getPermissionFromFormElementData_Submit() {
      $formElementData = array('permissions'=> array(
                                               $GLOBALS['UGROUP_ANONYMOUS'] => array(0 => 'PLUGIN_TRACKER_FIELD_UPDATE',
                                                                            1 => 'PLUGIN_TRACKER_FIELD_SUBMIT'),
                                               $GLOBALS['UGROUP_REGISTERED'] => array(0 => 'PLUGIN_TRACKER_FIELD_SUBMIT',
                                                                            1 => 'PLUGIN_TRACKER_FIELD_READ'),

          ) );

      $ff = Tracker_FormElementFactory::instance();
      $elmtId = 134;

      $ugroups_permissions = $ff->getPermissionsFromFormElementData($elmtId, $formElementData);      
      $this->assertTrue(isset($ugroups_permissions[$elmtId]));
      $this->assertTrue(isset($ugroups_permissions[$elmtId][1]));//ugroup_anonymous
      $this->assertTrue(isset($ugroups_permissions[$elmtId][2]));//ugroup_registered
      $this->assertTrue(isset($ugroups_permissions[$elmtId][1]['others']));
      $this->assertEqual($ugroups_permissions[$elmtId][1]['others'], 1);
      $this->assertEqual($ugroups_permissions[$elmtId][2]['others'], 0);
      $this->assertTrue(isset($ugroups_permissions[$elmtId][2]['submit']));
      $this->assertEqual($ugroups_permissions[$elmtId][2]['submit'], 'on');
    }

    public function testGetFieldById() {
        $fe_fact  = new Tracker_FormElementFactoryTestVersion();
        $date     = new MockTracker_FormElement_Field_Date();
        $fieldset = new MockTracker_FormElement_Container_Fieldset();
        
        $fe_fact->setReturnReference('getFormElementById', $date, array(123));
        $fe_fact->setReturnReference('getFormElementById', $fieldset, array(456));
        
        $this->assertIsA($fe_fact->getFieldById(123), 'Tracker_FormElement_Field');
        $this->assertNull($fe_fact->getFieldById(456), 'A fieldset is not a Field');
        $this->assertNull($fe_fact->getFieldById(789), 'Field does not exist');
        
    }

    public function testDeductNameFromLabel() {
        $label = 'titi est dans la brouSSe avec ro,min"ééééet';
        $tf = new Tracker_FormElementFactoryTestVersion();
        $label = $tf->deductNameFromLabel($label);
        $this->assertEqual($label, 'titi_est_dans_la_brousse_avec_rominet');
    }

}
?>