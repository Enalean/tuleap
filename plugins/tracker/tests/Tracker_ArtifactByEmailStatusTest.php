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

class Tracker_ArtifactByEmailStatusTest extends TuleapTestCase
{
    private $tracker;
    private $tracker_plugin_conf;

    public function setUp()
    {
        parent::setUp();

        $this->tracker             = mock('Tracker');
        $this->tracker_plugin_conf = mock('Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig');
    }

    public function itAcceptsArtifactByInsecureEmailWhenSemanticIsDefined()
    {
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));

        $field_title = mock('Tracker_FormElement_Field_String');
        stub($this->tracker)->getTitleField()->returns($field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));

        $field_description = mock('Tracker_FormElement_Field_Text');
        stub($this->tracker)->getDescriptionField()->returns($field_description);
        stub($this->tracker)->getFormElementFields()->returns(array($field_title, $field_description));
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function itAcceptsArtifactByInsecureEmailWhenRequiredFieldsAreValid()
    {
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        $field_title       = mock('Tracker_FormElement_Field_String');
        $field_description = mock('Tracker_FormElement_Field_Text');
        stub($this->tracker)->getTitleField()->returns($field_title);
        stub($this->tracker)->getDescriptionField()->returns($field_description);
        stub($this->tracker)->getFormElementFields()->returns(array($field_title, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function itDoesNotAcceptArtifactByInsecureEmailWhenRequiredFieldsAreInvalid()
    {
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);
        $field_title       = mock('Tracker_FormElement_Field_String');
        stub($field_title)->getId()->returns(1);
        stub($this->tracker)->getTitleField()->returns($field_title);
        $field_description = mock('Tracker_FormElement_Field_Text');
        stub($field_description)->getId()->returns(2);
        stub($this->tracker)->getDescriptionField()->returns($field_description);
        $another_field     = mock('Tracker_FormElement_Field_Text');
        stub($another_field)->getId()->returns(3);
        stub($another_field)->isRequired()->returns(true);
        stub($this->tracker)->getFormElementFields()->returns(array($field_title, $another_field, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function itDoesNotCreateArtifactInTokenMode()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function itUpdatesArtifactInTokenMode()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function itDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsDisabled()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function itUpdatesArtifactInTokenModeWhenMailGatewayIsInsecure()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function itDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsInsecureAndTrackerDisallowEmailGateway()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function itUpdatesArtifactInInsecureMode()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function itDoesNotUpdateArtifactInInsecureModeWhenTrackerEmailGatewayIsDisabled()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(false);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function itDoesNotUpdateArtifactInInsecureModeWhenTokenModeIsEnabled()
    {
        stub($this->tracker_plugin_conf)->isTokenBasedEmailgatewayEnabled()->returns(true);
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(false);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function itChecksFieldValidity()
    {
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $field_title       = mock('Tracker_FormElement_Field_String');
        stub($field_title)->getId()->returns(1);
        $field_description = mock('Tracker_FormElement_Field_Text');
        stub($field_description)->getId()->returns(2);
        $another_field     = mock('Tracker_FormElement_Field_Text');
        stub($another_field)->getId()->returns(3);
        stub($this->tracker)->getTitleField()->returns($field_title);
        stub($this->tracker)->getDescriptionField()->returns($field_description);
        stub($this->tracker)->getFormElementFields()->returns(array($field_title, $another_field, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));

        stub($field_title)->isRequired()->returns(true);
        stub($field_description)->isRequired()->returns(true);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));

        stub($another_field)->isRequired()->returns(true);
        $this->assertFalse($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));
    }

    public function itChecksSemantic()
    {
        stub($this->tracker_plugin_conf)->isInsecureEmailgatewayEnabled()->returns(true);
        stub($this->tracker)->isEmailgatewayEnabled()->returns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));

        $field_title = mock('Tracker_FormElement_Field_String');
        stub($this->tracker)->getTitleField()->returns($field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));

        $field_description = mock('Tracker_FormElement_Field_Text');
        stub($this->tracker)->getDescriptionField()->returns($field_description);
        $this->assertTrue($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));
    }
}
