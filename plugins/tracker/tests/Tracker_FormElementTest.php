<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

Mock::generate('Project');
Mock::generate('Tracker');
Mock::generate('Tracker_FormElement_Field_Selectbox');
Mock::generate('Tracker_FormElementFactory');

class Tracker_FormElementTest extends TuleapTestCase
{

    function testGetOriginalProjectAndOriginalTracker()
    {
        $project = new MockProject();
        $tracker = new MockTracker();
        $tracker->setReturnValue('getProject', $project);
        $original = new MockTracker_FormElement_Field_Selectbox();
        $original->setReturnValue('getTracker', $tracker);

        $element = $this->GivenAFormElementWithIdAndOriginalField(null, $original);

        $this->assertEqual($tracker, $element->getOriginalTracker());
        $this->assertEqual($project, $element->getOriginalProject());
    }

    function testGetOriginalFieldIdShouldReturnTheFieldId()
    {
        $original = $this->GivenAFormElementWithIdAndOriginalField(112, null);
        $element = $this->GivenAFormElementWithIdAndOriginalField(null, $original);
        $this->assertEqual($element->getOriginalFieldId(), 112);
    }

    function testGetOriginalFieldIdShouldReturn0IfNoOriginalField()
    {
        $element = $this->GivenAFormElementWithIdAndOriginalField(null, null);
        $this->assertEqual($element->getOriginalFieldId(), 0);
    }

    protected function GivenAFormElementWithIdAndOriginalField($id, $originalField)
    {
        return new Tracker_FormElement_StaticField_Separator($id, null, null, null, null, null, null, null, null, null, null, $originalField);
    }

    public function testDisplayUpdateFormShouldDisplayAForm()
    {
        $formElement = $this->GivenAFormElementWithIdAndOriginalField(null, null);

        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnValue('getUsedFormElementForTracker', array());

        $formElement->setTracker(new MockTracker());
        $formElement->setFormElementFactory($factory);

        $content     = $this->WhenIDisplayAdminFormElement($formElement);

        $this->assertPattern('%Update%', $content);
        $this->assertPattern('%</form>%', $content);
    }

    private function WhenIDisplayAdminFormElement($formElement)
    {
        $GLOBALS['Language']->setReturnValue('getText', 'Update', array('plugin_tracker_include_type', 'upd_label', '*'));

        $tracker_manager = mock('TrackerManager');
        $user            = mock('PFUser');
        $request         = mock('HTTPRequest');

        ob_start();
        $formElement->displayAdminFormElement($tracker_manager, $request, $user);
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}

class Tracker_FormElementJsonTest extends TuleapTestCase
{

    private $user;
    private $form_element;

    public function setUp()
    {
        parent::setUp();
        $this->form_element = aStringField()->withId(300)->withLabel("My field")->withName('my_field')->build();

        $this->user = aUser()->build();
    }

    public function itHasAllFieldElementsInJsonReadyArray()
    {
        $this->assertEqual(
            $this->form_element->fetchFormattedForJson(),
            array(
                'id' => 300,
                'label' => 'My field',
                'name'  => 'my_field',
            )
        );
    }
}

class Tracker_FormElement_UserPermissionsTest extends TuleapTestCase
{

    private $user;
    private $form_element;
    private $workflow_user;

    public function setUp()
    {
        parent::setUp();
        $this->form_element = aStringField()->withId(300)->withLabel("My field")->withName('my_field')->build();

        $this->user = aUser()->build();
        $this->workflow_user = new Tracker_Workflow_WorkflowUser();
    }

    public function itGrantsReadAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanRead($this->workflow_user));
    }

    public function itGrantsUpdateAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanUpdate($this->workflow_user));
    }

    public function itGrantsSubmitAccessToWorkflowUser()
    {
        $this->assertTrue($this->form_element->userCanSubmit($this->workflow_user));
    }
}

class Tracker_FormElement__ExportPermissionsToXmlTest extends TuleapTestCase
{

    public function testPermissionsExport()
    {
        $ugroups = array(
            'UGROUP_1' => 1,
            'UGROUP_2' => 2,
            'UGROUP_3' => 3,
            'UGROUP_4' => 4,
            'UGROUP_5' => 5
        );

        $field_01 = partial_mock(
            'Tracker_FormElement_Field_String',
            array(
                'getId',
                'getPermissionsByUgroupId',
                'isUsed',
            )
        );

        stub($field_01)->getId()->returns(10);
        stub($field_01)->getPermissionsByUgroupId()->returns(array(
            2 => array('FIELDPERM_1'),
            4 => array('FIELDPERM_2'),
        ));
        stub($field_01)->isUsed()->returns(true);

        $xmlMapping['F'. $field_01->getId()] = $field_01->getId();
        $xml     = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><permissions/>');
        $field_01->exportPermissionsToXML($xml, $ugroups, $xmlMapping);

        $this->assertTrue(isset($xml->permission[0]));
        $this->assertTrue(isset($xml->permission[1]));

        $this->assertEqual((string)$xml->permission[0]['scope'], 'field');
        $this->assertEqual((string)$xml->permission[0]['ugroup'], 'UGROUP_2');
        $this->assertEqual((string)$xml->permission[0]['type'], 'FIELDPERM_1');
        $this->assertEqual((string)$xml->permission[0]['REF'], 'F10');

        $this->assertEqual((string)$xml->permission[1]['scope'], 'field');
        $this->assertEqual((string)$xml->permission[1]['ugroup'], 'UGROUP_4');
        $this->assertEqual((string)$xml->permission[1]['type'], 'FIELDPERM_2');
        $this->assertEqual((string)$xml->permission[1]['REF'], 'F10');
    }
}
