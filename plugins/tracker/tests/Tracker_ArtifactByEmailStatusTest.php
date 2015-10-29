<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

class Tracker_ArtifactByEmailStatusTest extends TuleapTestCase {
    private $tracker;
    private $tracker_plugin_conf;

    public function setUp() {
        parent::setUp();

        $this->tracker             = mock('Tracker');
        $this->tracker_plugin_conf = mock('TrackerPluginConfig');
    }

    public function itRejectsArtifactByEmailWhenGloballyDisabled() {
        $this->tracker_plugin_conf->setReturnValue('isEmailgatewayDisabled', true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifact());
    }

    public function itRejectsArtifactByEmailWhenTrackerDisabled() {
        $this->tracker_plugin_conf->setReturnValue('isEmailgatewayDisabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifact());
    }

    public function itAcceptsArtifactByInsecureEmailWhenSemanticIsDefined() {
        $this->tracker_plugin_conf->setReturnValue('isInsecureEmailgatewayEnabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact());

        $field_title = mock('Tracker_FormElement_Field_String');
        $this->tracker->setReturnValue('getTitleField', $field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact());

        $field_description = mock('Tracker_FormElement_Field_Text');
        $this->tracker->setReturnValue('getDescriptionField', $field_description);
        $this->tracker->setReturnValue('getFormElementFields', array($field_title, $field_description));
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact());
    }

    public function itOnlyAcceptsArtifactByEmailUpdateForTokenMail() {
        $this->tracker_plugin_conf->setReturnValue('isTokenBasedEmailgatewayEnabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact());
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifact());
    }

    public function itAcceptsArtifactByEmailUpdateForTokenMailInCompatibilityMode() {
        $this->tracker_plugin_conf->setReturnValue('isTokenBasedEmailgatewayEnabled', false);
        $this->tracker_plugin_conf->setReturnValue('isInsecureEmailgatewayEnabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifact());
    }

    public function itChecksFieldValidity() {
        $this->tracker_plugin_conf->setReturnValue('isInsecureEmailgatewayEnabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', true);

        $field_title       = mock('Tracker_FormElement_Field_String');
        $field_title->setReturnValue('getId', 1);
        $field_description = mock('Tracker_FormElement_Field_Text');
        $field_description->setReturnValue('getId', 2);
        $another_field     = mock('Tracker_FormElement_Field_Text');
        $another_field->setReturnValue('getId', 3);
        $this->tracker->setReturnValue('getTitleField', $field_title);
        $this->tracker->setReturnValue('getDescriptionField', $field_description);
        $this->tracker->setReturnValue('getFormElementFields', array($field_title, $another_field, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured());

        $field_title->setReturnValue('isRequired', true);
        $field_description->setReturnValue('isRequired', true);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured());

        $another_field->setReturnValue('isRequired', true);
        $this->assertFalse($tracker_artifactbyemailstatus->isRequiredFieldsConfigured());
    }

    public function itChecksSemantic() {
        $this->tracker_plugin_conf->setReturnValue('isInsecureEmailgatewayEnabled', true);
        $this->tracker->setReturnValue('isEmailgatewayEnabled', true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker, $this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured());

        $field_title = mock('Tracker_FormElement_Field_String');
        $this->tracker->setReturnValue('getTitleField', $field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured());

        $field_description = mock('Tracker_FormElement_Field_Text');
        $this->tracker->setReturnValue('getDescriptionField', $field_description);
        $this->assertTrue($tracker_artifactbyemailstatus->isSemanticConfigured());
    }
}