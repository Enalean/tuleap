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

abstract class Tracker_FormElementFactoryAbstract extends TuleapTestCase {

    protected function GivenAFormElementFactory() {
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getUsedFormElementForTracker', 'getEventManager', 'getDao'));
        $factory->setReturnValue('getUsedFormElementForTracker', array());
        $factory->setReturnValue('getEventManager', new MockEventManager());
        return $factory;
    }
}

class Tracker_FormElementFactoryTest extends Tracker_FormElementFactoryAbstract {

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
    
}
class Tracker_FormElementFactory_GetAllSharedFieldsOfATrackerTest extends Tracker_FormElementFactoryAbstract {

    public function itReturnsEmptyArrayWhenNoSharedFields() {
        $project_id = 1;
        $dar = TestHelper::arrayToDar();
        
        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);

        $this->ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, array());
    }
    
    public function itReturnsAllSharedFieldsThatTheTrackerExports() {
        $project_id = 1;
        
        $sharedRow1 = $this->createRow(999, 'text');
        $sharedRow2 = $this->createRow(666, 'date');
        
        $dar = TestHelper::arrayToDar(
                $sharedRow1,
                $sharedRow2
        );
        
        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);
        
        $textField = aTextField()->withId(999)->build();
        $dateField = aDateField()->withId(666)->build();

        $this->ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, array($textField, $dateField));
    }

    
    private function ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, $expectedResult) {
        $project = new MockProject();
        $project->setReturnValue('getId', $project_id);
        
        $this->assertEqual($factory->getProjectSharedFields($project), $expectedResult);
    }

    public function itReturnsTheFieldsIfUserCanReadTheOriginal() {
        $user       = mock('User');
        $project = new MockProject();
        
        $readableField   = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $targetOfReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $unReadableField = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($readableField, $unReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array(), array($unReadableField));
        $factory->setReturnValue('getSharedTargets', array($targetOfReadableField1, $targetOfReadableField2), array($readableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($readableField));
    }
    
    public function itReturnsTheFieldsIfUserCannotReadTheOriginalButAtleastOneOfTheTargets() {
        $user       = mock('User');
        $project = new MockProject();
        
        $unReadableField = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $targetOfUnReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $targetOfUnReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
                
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($unReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array($targetOfUnReadableField1, $targetOfUnReadableField2), array($unReadableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($unReadableField));
    }
    
    public function itReturnsACollectionOfUniqueOriginals() {
        $user       = mock('User');
        $project = new MockProject();
        
        $unReadableField = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $targetOfUnReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfUnReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
                
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($unReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array($targetOfUnReadableField1, $targetOfUnReadableField2), array($unReadableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($unReadableField));
    }

    private function GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id) {
        $dao = new MockTracker_FormElement_FieldDao();
        $dao->setReturnValue('searchProjectSharedFieldsOriginals', $dar, array($project_id));
        
        $factory = $this->GivenAFormElementFactory();
        $factory->setReturnValue('getDao', $dao);
        
        return $factory;
    }
    
    private function createRow($id, $type) {
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
                $this->createRow(999, 'text')
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

?>
