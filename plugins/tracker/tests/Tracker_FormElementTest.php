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

class Tracker_FormElementTest extends TuleapTestCase {

    function testGetOriginalProjectAndOriginalTracker() {
        $project = new MockProject();
        $tracker = new MockTracker();
        $tracker->setReturnValue('getProject', $project);
        $original = new MockTracker_FormElement_Field_Selectbox();
        $original->setReturnValue('getTracker', $tracker);

        $element = $this->GivenAFormElementWithIdAndOriginalField(null, $original);

        $this->assertEqual($tracker, $element->getOriginalTracker());
        $this->assertEqual($project, $element->getOriginalProject());
    }

    function testGetOriginalFieldIdShouldReturnTheFieldId() {
        $original = $this->GivenAFormElementWithIdAndOriginalField(112, null);
        $element = $this->GivenAFormElementWithIdAndOriginalField(null, $original);
        $this->assertEqual($element->getOriginalFieldId(), 112);
    }

    function testGetOriginalFieldIdShouldReturn0IfNoOriginalField() {
        $element = $this->GivenAFormElementWithIdAndOriginalField(null, null);
        $this->assertEqual($element->getOriginalFieldId(), 0);
    }

    protected function GivenAFormElementWithIdAndOriginalField($id, $originalField) {
        return new Tracker_FormElement_StaticField_Separator($id, null, null, null, null, null, null, null, null, null, null, $originalField);
    }

    public function testDisplayUpdateFormShouldDisplayAForm() {
        $formElement = $this->GivenAFormElementWithIdAndOriginalField(null, null);

        $factory = new MockTracker_FormElementFactory();
        $factory->setReturnValue('getUsedFormElementForTracker', array());

        $formElement->setTracker(new MockTracker());
        $formElement->setFormElementFactory($factory);

        $content     = $this->WhenIDisplayAdminFormElement($formElement);

        $this->assertPattern('%Update%', $content);
        $this->assertPattern('%</form>%', $content);
    }

    private function WhenIDisplayAdminFormElement($formElement) {
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

class Tracker_FormElement_exportToSOAPTest extends Tracker_FormElementTest {

    private $user;
    private $form_element;

    public function setUp() {
        parent::setUp();
        $to_stub_methods       = array('userCanRead', 'userCanUpdate', 'userCanSubmit');
        $this->form_element    = partial_mock('Tracker_FormElement_StaticField_Separator', $to_stub_methods);

        $this->user = aUser()->build();
    }

    public function itExportsReadPermissionsWhenUserHasJustReadPermission() {
        stub($this->form_element)->userCanRead()->returns(True);
        stub($this->form_element)->userCanUpdate()->returns(False);
        stub($this->form_element)->userCanSubmit()->returns(False);

        $result               = $this->form_element->exportCurrentUserPermissionsToSOAP($this->user);
        $expected_permissions = array('read');

        $this->assertEqual($result, $expected_permissions);
    }

    public function itExportsNoPermissionsWhenUserHasNone() {
        stub($this->form_element)->userCanRead()->returns(False);
        stub($this->form_element)->userCanUpdate()->returns(False);
        stub($this->form_element)->userCanSubmit()->returns(False);

        $expected_permissions = array();
        $result               = $this->form_element->exportCurrentUserPermissionsToSOAP($this->user);

        $this->assertEqual($result, $expected_permissions);
    }

    public function itExportsCanReadAndCanUpdatePermissions() {
        stub($this->form_element)->userCanRead()->returns(True);
        stub($this->form_element)->userCanUpdate()->returns(True);
        stub($this->form_element)->userCanSubmit()->returns(False);

        $result               = $this->form_element->exportCurrentUserPermissionsToSOAP($this->user);
        $expected_permissions = array('read', 'update');

        $this->assertEqual($result, $expected_permissions);
    }

    public function itExportsCanReadAndCanSubmitPermissions() {
        stub($this->form_element)->userCanRead()->returns(True);
        stub($this->form_element)->userCanUpdate()->returns(False);
        stub($this->form_element)->userCanSubmit()->returns(True);

        $expected_permissions = array('read', 'submit');
        $result               = $this->form_element->exportCurrentUserPermissionsToSOAP($this->user);

        $this->assertEqual($result, $expected_permissions);
    }

    public function itExportsAllPermissions() {
        stub($this->form_element)->userCanRead()->returns(True);
        stub($this->form_element)->userCanUpdate()->returns(True);
        stub($this->form_element)->userCanSubmit()->returns(True);

        $result               = $this->form_element->exportCurrentUserPermissionsToSOAP($this->user);
        $expected_permissions = array('read', 'update', 'submit');

        $this->assertEqual($result, $expected_permissions);
    }
}

class Tracker_FormElementJsonTest extends TuleapTestCase {

    private $user;
    private $form_element;

    public function setUp() {
        parent::setUp();
        $this->form_element = aStringField()->withId(300)->withLabel("My field")->withName('my_field')->build();

        $this->user = aUser()->build();
    }

    public function itHasAllFieldElementsInJsonReadyArray() {
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

?>
