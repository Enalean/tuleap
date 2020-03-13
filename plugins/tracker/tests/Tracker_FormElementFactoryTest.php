<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
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

abstract class Tracker_FormElementFactoryAbstract extends TuleapTestCase
{

    protected function GivenAFormElementFactory()
    {
        $factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('getUsedFormElementForTracker')->andReturns(array());
        $factory->shouldReceive('getEventManager')->andReturns(\Mockery::spy(\EventManager::class));
        return $factory;
    }
}

class Tracker_FormElementFactoryTest extends Tracker_FormElementFactoryAbstract
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
    }

    public function test_saveObject()
    {
        $user          = \Mockery::spy(\PFUser::class);
        $tracker       = \Mockery::spy(\Tracker::class);

        $a_formelement = \Mockery::spy(\Tracker_FormElement_Container_Fieldset::class);

        $a_formelement->shouldReceive('afterSaveObject')->once();
        $a_formelement->shouldReceive('setId')->with(66)->once();
        $a_formelement->shouldReceive('getFlattenPropertiesValues')->andReturns(array());

        $tff = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $tff->shouldReceive('createFormElement')->andReturns(66);

        $this->assertEqual($tff->saveObject($tracker, $a_formelement, 0, $user, false), 66);
    }

    //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)
    public function test_getPermissionFromFormElementData()
    {
        $formElementData = array('permissions' => array(
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
    }

    public function test_getPermissionFromFormElementData_Submit()
    {
        $formElementData = array('permissions' => array(
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

    public function testGetFieldById()
    {
        $fe_fact  = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $date     = \Mockery::spy(\Tracker_FormElement_Field_Date::class);
        $fieldset = \Mockery::spy(\Tracker_FormElement_Container_Fieldset::class);

        $fe_fact->shouldReceive('getFormElementById')->with(123)->andReturn($date);
        $fe_fact->shouldReceive('getFormElementById')->with(456)->andReturn($fieldset);
        $fe_fact->shouldReceive('getFormElementById')->with(789)->andReturn(null);

        $this->assertIsA($fe_fact->getFieldById(123), 'Tracker_FormElement_Field');
        $this->assertNull($fe_fact->getFieldById(456), 'A fieldset is not a Field');
        $this->assertNull($fe_fact->getFieldById(789), 'Field does not exist');
    }

    public function testDeductNameFromLabel()
    {
        $label = 'titi est dans la brouSSe avec ro,min"ééééet';
        $tf = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $label = $tf->deductNameFromLabel($label);
        $this->assertEqual($label, 'titi_est_dans_la_brousse_avec_rominet');
    }

    public function testDisplayCreateFormShouldDisplayAForm()
    {
        $factory = $this->GivenAFormElementFactory();
        $content = $this->WhenIDisplayCreateFormElement($factory);

        $this->assertPattern('%Create a new Separator%', $content);
        $this->assertPattern('%</form>%', $content);
    }

    private function WhenIDisplayCreateFormElement($factory)
    {
        $GLOBALS['Language']->shouldReceive('getText')->with('plugin_tracker_formelement_admin', 'separator_label')->andReturns('Separator');

        $tracker_manager = \Mockery::spy(\TrackerManager::class);
        $user            = \Mockery::spy(\PFUser::class);
        $request         = \Mockery::spy(\HTTPRequest::class);
        $tracker         = \Mockery::spy(\Tracker::class);

        ob_start();
        $factory->displayAdminCreateFormElement($tracker_manager, $request, $user, 'separator', $tracker);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
class Tracker_FormElementFactory_GetAllSharedFieldsOfATrackerTest extends Tracker_FormElementFactoryAbstract
{

    public function itReturnsEmptyArrayWhenNoSharedFields()
    {
        $project_id = 1;
        $dar = TestHelper::emptyDar();

        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);

        $this->ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, array());
    }

    public function itReturnsAllSharedFieldsThatTheTrackerExports()
    {
        $project_id = 1;

        $sharedRow1 = $this->createRow(999, 'text');
        $sharedRow2 = $this->createRow(666, 'date');

        $dar = TestHelper::arrayToDar(
            $sharedRow1,
            $sharedRow2
        );

        $factory = $this->GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);

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


    private function ThenICompareProjectSharedFieldsWithExpectedResult($factory, $project_id, $expectedResult)
    {
        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('getID')->andReturns($project_id);

        $this->assertEqual($factory->getProjectSharedFields($project), $expectedResult);
    }

    public function itReturnsTheFieldsIfUserCanReadTheOriginalAndAllTargets()
    {
        $user    = \Mockery::spy(\PFUser::class);
        $project = \Mockery::spy(\Project::class);

        $readableField          = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $targetOfReadableField1 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $targetOfReadableField2 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $unReadableField        = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(false);

        $factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('getProjectSharedFields')->with($project)->andReturns(array($readableField, $unReadableField));
        $factory->shouldReceive('getSharedTargets')->with($unReadableField)->andReturns(array());
        $factory->shouldReceive('getSharedTargets')->with($readableField)->andReturns(array($targetOfReadableField1, $targetOfReadableField2));

        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($readableField));
    }

    public function itDoesntReturnAnythingIfUserCannotReadTheOriginalAndAllTheTargets()
    {
        $user       = \Mockery::spy(\PFUser::class);
        $project = \Mockery::spy(\Project::class);

        $aReadableField         = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $targetOfReadableField1 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(false);
        $targetOfReadableField2 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);

        $factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('getProjectSharedFields')->with($project)->andReturns(array($aReadableField));
        $factory->shouldReceive('getSharedTargets')->with($aReadableField)->andReturns(array($targetOfReadableField1, $targetOfReadableField2));

        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array());
    }

    public function itReturnsACollectionOfUniqueOriginals()
    {
        $user       = \Mockery::spy(\PFUser::class);
        $project = \Mockery::spy(\Project::class);

        $aReadableField         = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $targetOfReadableField1 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);
        $targetOfReadableField2 = mockery_stub(\Tracker_FormElement::class)->userCanRead($user)->returns(true);

        $factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $factory->shouldReceive('getProjectSharedFields')->with($project)->andReturns(array($aReadableField));
        $factory->shouldReceive('getSharedTargets')->with($aReadableField)->andReturns(array($targetOfReadableField1, $targetOfReadableField2));

        $this->assertEqual($factory->getSharedFieldsReadableBy($user, $project), array($aReadableField));
    }

    private function GivenSearchAllSharedTargetsOfProjectReturnsDar($dar, $project_id)
    {
        $dao = \Mockery::spy(\Tracker_FormElement_FieldDao::class);
        $dao->shouldReceive('searchProjectSharedFieldsOriginals')->with($project_id)->andReturns($dar);

        $factory = $this->GivenAFormElementFactory();
        $factory->shouldReceive('getDao')->andReturns($dao);

        return $factory;
    }

    private function createRow($id, $type)
    {
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

    public function testGetFieldFromTrackerAndSharedField()
    {
        $original_field_dar = TestHelper::arrayToDar(
            $this->createRow(999, 'text')
        );
        $dao = \Mockery::spy(\Tracker_FormElement_FieldDao::class);
        $dao->shouldReceive('searchFieldFromTrackerIdAndSharedFieldId')->with(66, 123)->andReturns($original_field_dar);

        $factory = $this->GivenAFormElementFactory();
        $factory->shouldReceive('getDao')->andReturns($dao);

        $originalField = aTextField()->withId(999)->build();

        $tracker = aTracker()->withId(66)->build();
        $exportedField = aTextField()->withId(123)->build();
        $this->assertEqual($factory->getFieldFromTrackerAndSharedField($tracker, $exportedField), $originalField);
    }
}



class Tracker_SharedFormElementFactoryDuplicateTest extends TuleapTestCase
{

    private $project_id;
    private $template_id;
    private $dao;
    private $factory;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->project_id  = 3;
        $this->template_id = 29;

        $this->dao     = \Mockery::spy(\Tracker_FormElement_FieldDao::class);
        $this->factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();

        stub($this->factory)->getDao()->returns($this->dao);
    }

    public function itDoesNothingWhenFieldMappingIsEmpty()
    {
        $template_project_field_ids = array();
        $new_project_shared_fields  = array();
        $field_mapping              = array();

        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);

        $this->dao->shouldReceive('updateOriginalFieldId')->never();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function itDoesNothingWhenThereIsNoSharedFieldInTheFieldMapping()
    {
        $template_project_field_ids = array(321);
        $new_project_shared_fields  = array();
        $field_mapping              = array(array('from' => 321, 'to' => 101));

        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);

        $this->dao->shouldReceive('updateOriginalFieldId')->never();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function itUpdatesTheOrginalFieldIdForEverySharedField()
    {
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

        $this->dao->shouldReceive('updateOriginalFieldId')->with(234, 777)->ordered();
        $this->dao->shouldReceive('updateOriginalFieldId')->with(567, 888)->ordered();

        $field_234 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        stub($this->factory)->getShareableFieldById(234)->returns($field_234);

        $field_567 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        stub($this->factory)->getShareableFieldById(567)->returns($field_567);

        $field_234->shouldReceive('fixOriginalValueIds')->with(array(3 => 4, 5 => 6))->ordered();
        $field_567->shouldReceive('fixOriginalValueIds')->with(array(1 => 2))->ordered();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }

    public function itDoesntUpdateWhenTheOriginalFieldIdRefersToAfieldOutsideTheTemplateProject()
    {
        $template_project_field_ids = array(999, 103, 666);

        $new_project_internal_shared_field = array('id' => 234, 'original_field_id' => 666);
        $new_project_external_shared_field = array('id' => 567, 'original_field_id' => 555);
        $new_project_shared_fields         = array($new_project_internal_shared_field, $new_project_external_shared_field);

        $field_mapping        = array(array('from' => 999, 'to' => 234),
                                      array('from' => 103, 'to' => 567),
                                      array('from' => 666, 'to' => 777, 'values' => array(1 => 2, 3 => 4)));

        stub($this->dao)->searchProjectSharedFieldsTargets($this->project_id)->returns($new_project_shared_fields);
        stub($this->dao)->searchFieldIdsByGroupId($this->template_id)->returns($template_project_field_ids);

        $field_234 = \Mockery::spy(\Tracker_FormElement_Field_Shareable::class);
        stub($this->factory)->getShareableFieldById(234)->returns($field_234);

        $this->dao->shouldReceive('updateOriginalFieldId')->with(234, 777)->once();
        $field_234->shouldReceive('fixOriginalValueIds')->with(array(1 => 2, 3 => 4))->once();

        $this->factory->fixOriginalFieldIdsAfterDuplication($this->project_id, $this->template_id, $field_mapping);
    }
}

class Tracker_FormElementFactory_GetArtifactLinks extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user    = \Mockery::spy(\PFUser::class);
        $this->tracker = \Mockery::spy(\Tracker::class);
        $this->field   = \Mockery::spy(\Tracker_FormElement_Field_ArtifactLink::class);

        $this->factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($this->factory)->getUsedArtifactLinkFields($this->tracker)->returns(array($this->field));
    }

    public function itReturnsNullIfThereAreNoArtifactLinkFields()
    {
        $factory = \Mockery::mock(\Tracker_FormElementFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
        stub($factory)->getUsedArtifactLinkFields($this->tracker)->returns(array());
        $this->assertEqual($factory->getAnArtifactLinkField($this->user, $this->tracker), null);
    }

    public function itReturnsNullIfUserCannotSeeArtifactLinkField()
    {
        stub($this->field)->userCanRead($this->user)->returns(false);
        $this->assertEqual($this->factory->getAnArtifactLinkField($this->user, $this->tracker), null);
    }

    public function itReturnsFieldIfUserCanSeeArtifactLinkField()
    {
        stub($this->field)->userCanRead($this->user)->returns(true);
        $this->assertEqual($this->factory->getAnArtifactLinkField($this->user, $this->tracker), $this->field);
    }
}
