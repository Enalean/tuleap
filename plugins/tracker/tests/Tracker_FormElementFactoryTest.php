<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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
    'Tracker_FormElementFactory', 
    'Tracker_FormElementFactoryTestVersion', 
    array(
        'getInstanceFromRow',
        'getFormElementById',
        'createFormElement',
        'getFormElementDataForCreation'
    )
);

Mock::generate('Tracker_FormElement_FieldDao');

Mock::generate('Tracker_FormElement_Container_Fieldset');

Mock::generate('Tracker_FormElement_Field_Date');

Mock::generate('Tracker');
Mock::generate('TrackerManager');
Mock::generate('PFUser');
Mock::generate('Project');

require_once 'common/include/HTTPRequest.class.php';
Mock::generate('HTTPRequest');

require_once 'common/event/EventManager.class.php';
Mock::generate('EventManager');


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
        $user          = mock('PFUser');
        $tracker       = new MockTracker();
        
        $a_formelement = new MockTracker_FormElement_Container_Fieldset();
        
        $a_formelement->expect('setId', array(66));
        $a_formelement->expectOnce('afterSaveObject');
        $a_formelement->setReturnValue('getFlattenPropertiesValues', array());
        
        $tff = new Tracker_FormElementFactoryTestVersion();
        $tff->setReturnValue('createFormElement', 66);
        
        $this->assertEqual($tff->saveObject($tracker, $a_formelement, 0, $user, false), 66);
    }
    
    public function testImportFormElement() {
        $user_finder = mock('User\XML\Import\IFindUserFromXMLReference');

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
            '*',
            $user_finder
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
        
        $tracker = aTracker()->build();
        
        stub($a_formelement)->setTracker($tracker)->once();

        $f = $tf->getInstanceFromXML($tracker, $xml, $mapping, $user_finder);

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
        $user            = mock('PFUser');
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
        $dar = TestHelper::emptyDar();
        
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
        $dateField = aMockDateWithoutTimeField()->withId(666)->build();


        $project = new MockProject();
        $project->setReturnValue('getId', $project_id);

        $expected = $factory->getProjectSharedFields($project);

        $this->assertCount($expected, 2);

        $found_fields = array();
        foreach ($expected as $field) {
            if ($field instanceof Tracker_FormElement_Field_Date) {
                $found_fields['date'] = true;
                $this->assertEqual($field->getId(), 666);
            }

            if ($field instanceof Tracker_FormElement_Field_Text) {
                $found_fields['text'] = true;
                $this->assertEqual($field->getId(), 999);
            }
        }

         $this->assertEqual($found_fields, array('date' => true, 'text' => true));
    }

    
    private function ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, $expectedResult) {
        $project = new MockProject();
        $project->setReturnValue('getId', $project_id);

        $this->assertEqual($factory->getProjectSharedFields($project), $expectedResult);
    }

    public function itReturnsTheFieldsIfUserCanReadTheOriginalAndAllTargets() {
        $user       = mock('PFUser');
        $project = new MockProject();
        
        $readableField   = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $unReadableField = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($readableField, $unReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array(), array($unReadableField));
        $factory->setReturnValue('getSharedTargets', array($targetOfReadableField1, $targetOfReadableField2), array($readableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($readableField));
    }
    
    public function itDoesntReturnAnythingIfUserCannotReadTheOriginalAndAllTheTargets() {
        $user       = mock('PFUser');
        $project = new MockProject();
        
        $aReadableField         = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(false);
        $targetOfReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
                
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($aReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array($targetOfReadableField1, $targetOfReadableField2), array($aReadableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array());
    }
    
    public function itReturnsACollectionOfUniqueOriginals() {
        $user       = mock('PFUser');
        $project = new MockProject();
        
        $aReadableField         = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField1 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
        $targetOfReadableField2 = stub('Tracker_FormElement_Field_SelectBox')->userCanRead($user)->returns(true);
                
        $factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getProjectSharedFields', 'getSharedTargets'));
        $factory->setReturnValue('getProjectSharedFields', array($aReadableField), array($project));
        $factory->setReturnValue('getSharedTargets', array($targetOfReadableField1, $targetOfReadableField2), array($aReadableField));
        
        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($aReadableField));
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



class Tracker_SharedFormElementFactoryDuplicateTest extends TuleapTestCase {

    private $project_id;
    private $template_id;
    private $dao;
    private $factory;
    
    public function setUp() {
        parent::setUp();
        
        $this->project_id  = 3;
        $this->template_id = 29;
        
        $this->dao     = mock('Tracker_FormElement_FieldDao');
        $this->factory = TestHelper::getPartialMock('Tracker_FormElementFactory', array('getDao', 'getShareableFieldById'));
        
        stub($this->factory)->getDao()->returns($this->dao);
    }
    
    public function itDoesNothingWhenFieldMappingIsEmpty() {
        $template_project_field_ids = array();
        $new_project_shared_fields  = array();
        $field_mapping              = array();
        
        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);
        
        $this->dao->expectNever('updateOriginalFieldId');
        
        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }
    
    public function itDoesNothingWhenThereIsNoSharedFieldInTheFieldMapping() {
        $template_project_field_ids = array(321);
        $new_project_shared_fields  = array();
        $field_mapping              = array(array('from' => 321, 'to' => 101));
        
        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);
        
        $this->dao->expectNever('updateOriginalFieldId');
        
        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id,   $field_mapping);
    }
    
    public function itUpdatesTheOrginalFieldIdForEverySharedField() {
        $template_project_field_ids = array(999, 103, 555, 666);
        
        $new_project_shared_field_1 = array('id' => 234, 'original_field_id' => 666);
        $new_project_shared_field_2 = array('id' => 567, 'original_field_id' => 555);
        $new_project_shared_fields  = array($new_project_shared_field_1, $new_project_shared_field_2);
        
        $field_mapping              = array(array('from' => 999, 'to' => 234),
                                            array('from' => 103, 'to' => 567),
                                            array('from' => 555, 'to' => 888, 'values' => array(1 => 2)),
                                            array('from' => 666, 'to' => 777, 'values' => array(3 => 4, 5 => 6)));
        
        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);        
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);
        
        $this->dao->expectAt(0, 'updateOriginalFieldId', array(234, 777));
        $this->dao->expectAt(1, 'updateOriginalFieldId', array(567, 888));
        
        $field_234 = mock('Tracker_FormElement_Field_Shareable');
        stub($this->factory)->getShareableFieldById(234)->returns($field_234);
        
        $field_567 = mock('Tracker_FormElement_Field_Shareable');
        stub($this->factory)->getShareableFieldById(567)->returns($field_567);
        
        $field_234->expectAt(0, 'fixOriginalValueIds', array(array(3 => 4, 5 => 6)));
        $field_567->expectAt(1, 'fixOriginalValueIds', array(array(1 => 2)));
        
        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function itDoesntUpdateWhenTheOriginalFieldIdRefersToAfieldOutsideTheTemplateProject() {
        $template_project_field_ids = array(999, 103, 666);
        
        $new_project_internal_shared_field = array('id' => 234, 'original_field_id' => 666);
        $new_project_external_shared_field = array('id' => 567, 'original_field_id' => 555);
        $new_project_shared_fields         = array($new_project_internal_shared_field, $new_project_external_shared_field);
        
        $field_mapping        = array(array('from' => 999, 'to' => 234),
                                      array('from' => 103, 'to' => 567),
                                      array('from' => 666, 'to' => 777, 'values' => array(1 => 2, 3 => 4)));
        
        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);
        
        $field_234 = mock('Tracker_FormElement_Field_Shareable');
        stub($this->factory)->getShareableFieldById(234)->returns($field_234);
        
        $this->dao->expectOnce('updateOriginalFieldId', array(234, 777));
        $field_234->expectOnce('fixOriginalValueIds', array(array(1 => 2, 3 => 4)));
        
        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }
}

class Tracker_FormElementFactory_GetArtifactLinks extends TuleapTestCase {

    public function setUp() {
        parent::setUp();
        $this->user    = mock('PFUser');
        $this->tracker = mock('Tracker');
        $this->field   = mock('Tracker_FormElement_Field_ArtifactLink');

        $this->factory = partial_mock('Tracker_FormElementFactory', array('getUsedArtifactLinkFields'));
        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($this->field));
    }

    public function itReturnsNullIfThereAreNoArtifactLinkFields() {
        $factory = partial_mock('Tracker_FormElementFactory', array('getUsedArtifactLinkFields'));
        stub($factory)->getUsedArtifactLinkFields($this->tracker)->returns(array());
        $this->assertEqual($factory->getAnArtifactLinkField($this->user, $this->tracker), null);
    }

    public function itReturnsNullIfUserCannotSeeArtifactLinkField() {
        stub($this->field)->userCanRead($this->user)->returns(false);
        $this->assertEqual($this->factory->getAnArtifactLinkField($this->user, $this->tracker), null);
    }

    public function itReturnsFieldIfUserCanSeeArtifactLinkField() {
        stub($this->field)->userCanRead($this->user)->returns(true);
        $this->assertEqual($this->factory->getAnArtifactLinkField($this->user, $this->tracker), $this->field);
    }

}

class ArrayDoesntContainExpectation extends SimpleExpectation {
    private $should_not_exist;
    public function __construct(array $should_not_exist) {
        parent::__construct();
        $this->should_not_exist = $should_not_exist;
    }

    public function test(array $array_to_test) {
        return count(array_intersect($array_to_test, $this->should_not_exist)) === 0;
    }

    public function testMessage(array $array_to_test) {
        return "Submitted array still contains: (".implode(', ', array_intersect($array_to_test, $this->should_not_exist)).")";
    }
}

class Tracker_FormElementFactory_GetUsedFieldsForSOAP extends TuleapTestCase {

    private $tracker;
    private $factory;

    public function setUp() {
        parent::setUp();
        $this->tracker = mock('Tracker');
        $this->factory = partial_mock('Tracker_FormElementFactory', array('getUsedFormElementsByType'), array());
        stub($this->factory)->getUsedFormElementsByType()->returns(array());
    }

    public function itFiltersOutFieldsThatAreAlreadyReturnedBySOAPBasicInfo() {
        $elements_to_exclude_for_soap = array(
            'aid',
            'lud',
            'subby',
            'subon',
            'cross',
            'fieldset',
            'column',
            'linebreak',
            'separator',
            'staticrichtext',
        );
        expect($this->factory)->getUsedFormElementsByType($this->tracker, new ArrayDoesntContainExpectation($elements_to_exclude_for_soap))->once();

        $this->factory->getUsedFieldsForSOAP($this->tracker);
    }
}

?>
