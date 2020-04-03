<?php
/**
 * Copyright (c) Enalean, 2015 - present. All Rights Reserved.
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

class Tracker_ArtifactByEmailStatusTest extends \PHPUnit\Framework\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    private $tracker;
    private $tracker_plugin_conf;

    protected function setUp(): void
    {
        $this->tracker             = \Mockery::spy(\Tracker::class);
        $this->tracker_plugin_conf = \Mockery::spy(\Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig::class);
    }

    public function testItAcceptsArtifactByInsecureEmailWhenSemanticIsDefined(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));

        $field_title = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $this->tracker->shouldReceive('getTitleField')->andReturns($field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));

        $field_description = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $this->tracker->shouldReceive('getDescriptionField')->andReturns($field_description);
        $this->tracker->shouldReceive('getFormElementFields')->andReturns(array($field_title, $field_description));
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItAcceptsArtifactByInsecureEmailWhenRequiredFieldsAreValid(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);
        $field_title       = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field_description = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $this->tracker->shouldReceive('getTitleField')->andReturns($field_title);
        $this->tracker->shouldReceive('getDescriptionField')->andReturns($field_description);
        $this->tracker->shouldReceive('getFormElementFields')->andReturns(array($field_title, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItDoesNotAcceptArtifactByInsecureEmailWhenRequiredFieldsAreInvalid(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);
        $field_title       = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field_title->shouldReceive('getId')->andReturns(1);
        $this->tracker->shouldReceive('getTitleField')->andReturns($field_title);
        $field_description = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $field_description->shouldReceive('getId')->andReturns(2);
        $this->tracker->shouldReceive('getDescriptionField')->andReturns($field_description);
        $another_field     = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $another_field->shouldReceive('getId')->andReturns(3);
        $another_field->shouldReceive('isRequired')->andReturns(true);
        $this->tracker->shouldReceive('getFormElementFields')->andReturns(array($field_title, $another_field, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItDoesNotCreateArtifactInTokenMode(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItUpdatesArtifactInTokenMode(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsDisabled(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItUpdatesArtifactInTokenModeWhenMailGatewayIsInsecure(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsInsecureAndTrackerDisallowEmailGateway(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItUpdatesArtifactInInsecureMode(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInInsecureModeWhenTrackerEmailGatewayIsDisabled(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(false);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInInsecureModeWhenTokenModeIsEnabled(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isTokenBasedEmailgatewayEnabled')->andReturns(true);
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(false);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function testItChecksFieldValidity(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $field_title       = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $field_title->shouldReceive('getId')->andReturns(1);
        $field_description = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $field_description->shouldReceive('getId')->andReturns(2);
        $another_field     = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $another_field->shouldReceive('getId')->andReturns(3);
        $this->tracker->shouldReceive('getTitleField')->andReturns($field_title);
        $this->tracker->shouldReceive('getDescriptionField')->andReturns($field_description);
        $this->tracker->shouldReceive('getFormElementFields')->andReturns(array($field_title, $another_field, $field_description));

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));

        $field_title->shouldReceive('isRequired')->andReturns(true);
        $field_description->shouldReceive('isRequired')->andReturns(true);
        $this->assertTrue($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));

        $another_field->shouldReceive('isRequired')->andReturns(true);
        $this->assertFalse($tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));
    }

    public function testItChecksSemantic(): void
    {
        $this->tracker_plugin_conf->shouldReceive('isInsecureEmailgatewayEnabled')->andReturns(true);
        $this->tracker->shouldReceive('isEmailgatewayEnabled')->andReturns(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));

        $field_title = \Mockery::spy(\Tracker_FormElement_Field_String::class);
        $this->tracker->shouldReceive('getTitleField')->andReturns($field_title);
        $this->assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));

        $field_description = \Mockery::spy(\Tracker_FormElement_Field_Text::class);
        $this->tracker->shouldReceive('getDescriptionField')->andReturns($field_description);
        $this->assertTrue($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));
    }
}
