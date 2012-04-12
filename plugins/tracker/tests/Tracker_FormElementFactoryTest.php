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

require_once 'Test_Tracker_FormElement_Builder.php';

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElementFactory.class.php');
Mock::generatePartial(
    'Tracker_FormElementFactory', 
    'Tracker_FormElementFactoryTestVersion', 
    array(
        'getInstanceFromRow',
        'getFormElementById',
        'createFormElement',        
    )
);

Mock::generate('Tracker_FormElement_FieldDao');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Container_Fieldset.class.php');
Mock::generate('Tracker_FormElement_Container_Fieldset');

require_once(dirname(__FILE__).'/../include/Tracker/FormElement/Tracker_FormElement_Field_Date.class.php');
Mock::generate('Tracker_FormElement_Field_Date');

require_once(dirname(__FILE__).'/../include/Tracker/Tracker.class.php');
require_once(dirname(__FILE__).'/Test_Tracker_Builder.php');

Mock::generate('Tracker');
Mock::generate('TrackerManager');
Mock::generate('User');
Mock::generate('Project');

require_once 'common/include/HTTPRequest.class.php';
Mock::generate('HTTPRequest');

require_once 'common/event/EventManager.class.php';
Mock::generate('EventManager');

if (!defined('TRACKER_BASE_URL')) {
    define('TRACKER_BASE_URL', '/coin');
}

class Tracker_FormElementFactoryTest extends TuleapTestCase {

    public function test_saveObject() {
        $user          = new MockUser();
        $tracker       = new MockTracker();
        
        $a_formelement = new MockTracker_FormElement_Container_Fieldset();
        
        $a_formelement->expect('setId', array(66));
        $a_formelement->expectOnce('afterSaveObject');
        $a_formelement->setReturnValue('getFlattenPropertiesValues', array());
        
        $tff = new Tracker_FormElementFactoryTestVersion();
        $tff->setReturnValue('createFormElement', 66);
        
        $this->assertEqual($tff->saveObject($tracker, $a_formelement, 0, $user), 66);
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
                    'original_field_id'=> null,
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

    public function testDisplayCreateFormShouldDisplayAForm() {
        $factory = $this->GivenAFormElementFactory();
        $content = $this->WhenIDisplayCreateFormElement($factory);

        $this->assertPattern('%Create a new Separator%', $content);
        $this->assertPattern('%</form>%', $content);
    }
    
    private function GivenAFormElementFactory() {
        $factory         = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getUsedFormElementForTracker', 'getEventManager', 'getDao'));
        $factory->setReturnValue('getUsedFormElementForTracker', array());
        $factory->setReturnValue('getEventManager', new MockEventManager());
        return $factory;
    }
    
    private function WhenIDisplayCreateFormElement($factory) {
        $GLOBALS['Language']->setReturnValue('getText', 'Separator', array('plugin_tracker_formelement_admin','separator_label'));
        
        $tracker_manager = new MockTrackerManager();
        $user            = new MockUser();
        $request         = new MockHTTPRequest();
        $tracker         = new MockTracker();
        
        ob_start();
        $factory->displayAdminCreateFormElement($tracker_manager, $request, $user, 'separator', $tracker);
        $content = ob_get_contents();
        ob_end_clean();
        
        return $content;
    }
    
    public function testGetAllSharedFieldsOfATrackerShouldReturnsEmptyArrayWhenNoSharedFields() {
        $project_id = 1;
        $dar = TestHelper::arrayToDar();
        
        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);

        $this->ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, array());
    }
    
    public function testGetAllSharedFieldsOfATrackerReturnsAllSharedFieldsThatTheTrackerExports() {
        $project_id = 1;
        
        $dar = TestHelper::arrayToDar(
                $this->createDar(999, 'text'),
                $this->createDar(666, 'date')
        );
        
        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);
        
        $textField = aTextField()->withId(999)->build();
        $dateField = aDateField()->withId(666)->build();

        $this->ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, array($textField, $dateField));
    }
    
    private function GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id) {
        $dao = new MockTracker_FormElement_FieldDao();
        $dao->setReturnValue('searchProjectSharedFieldsOriginals', $dar, array($project_id));
        
        $factory = $this->GivenAFormElementFactory();
        $factory->setReturnValue('getDao', $dao);
        
        return $factory;
    }
    
    private function ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, $expectedResult) {
        $project = new MockProject();
        $project->setReturnValue('getId', $project_id);
        
        $this->assertEqual($factory->getProjectSharedFields($project), $expectedResult);
    }
    
    private function createDar($id, $type) {
        return array('id' => $id, 
                     'formElement_type' => $type,
                     'tracker_id' => null,
                     'parent_id' => null,
                     'name' => null,
                     'label' => null,
                     'description' => null,
                     'use_it' => null,
                     'scope' => null,
                     'required' => null,
                     'notifications' => null,
                     'rank' => null,
                     'original_field_id' => null);
    }
    
    public function testGetFieldFromTrackerAndSharedField() {
        $original_field_dar = TestHelper::arrayToDar(
                $this->createDar(999, 'text')
        );
        $dao = new MockTracker_FormElement_FieldDao();
        $dao->setReturnValue('searchFieldFromTrackerIdAndSharedFieldId', $original_field_dar, array(66, 123));

        $factory = $this->GivenAFormElementFactory();
        $factory->setReturnValue('getDao', $dao);

        $originalField = aTextField()->withId(999)->build();

                
        $tracker = aTracker()->withId(66)->build();
        $exportedField = aTextField()->withId(123)->build();
        $this->assertEqual($factory->getFieldFromTrackerAndSharedField($tracker, $exportedField), $originalField);
    }
}



class Tracker_SharedFormElementFactoryDuplicateTest extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->form_element_factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getDao'));
    }
    public function itDoesNothingWhenFieldMappingIsEmpty() {
        $project_id = 3;
        $no_shared_fields_copied = array();
        $dao = stub('Tracker_FormElement_FieldDao')->searchProjectSharedFieldsTargets($project_id)->returns($no_shared_fields_copied);        
        $dao->expectNever('updateOriginalFieldId');
        stub($this->form_element_factory)->getDao()->returns($dao);
        $field_mapping = array();
        $this->form_element_factory->fixOriginalFieldIdsAfterDuplication($field_mapping, $project_id);
    }
    
    public function itDoesNothingWhenThereIsNoSharedFieldInTheFieldMapping() {
        $project_id = 3;
        $no_shared_fields_copied = array();
        $dao = stub('Tracker_FormElement_FieldDao')->searchProjectSharedFieldsTargets($project_id)->returns($no_shared_fields_copied);        
        $dao->expectNever('updateOriginalFieldId');
        stub($this->form_element_factory)->getDao()->returns($dao);
        $field_mapping = array('321' => '101');
        $this->form_element_factory->fixOriginalFieldIdsAfterDuplication($field_mapping, $project_id);
    }
    
    public function itUpdatesTheOrginalFieldIdForEverySharedField() {
        $project_id = 3;
                
        $shared_field1 = array('id' => 234, 'original_field_id' => 666);
        $shared_field2 = array('id' => 567, 'original_field_id' => 555);
        $copied_shared_fields = array($shared_field1, $shared_field2);
        $dao = stub('Tracker_FormElement_FieldDao')->searchProjectSharedFieldsTargets($project_id)->returns($copied_shared_fields);        
        $dao->expectAt(0, 'updateOriginalFieldId', array(234, 777));
        $dao->expectAt(1, 'updateOriginalFieldId', array(567, 888));
        stub($this->form_element_factory)->getDao()->returns($dao);
        $field_mapping = array(999 => 234,
                               103 => 567,
                               555 => 888,
                               666 => 777);
        $this->form_element_factory->fixOriginalFieldIdsAfterDuplication($field_mapping, $project_id);
    }

}
?>
