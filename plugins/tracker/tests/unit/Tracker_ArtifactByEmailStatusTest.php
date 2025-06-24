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

declare(strict_types=1);

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Tracker;
use Tuleap\Tracker\Test\Stub\RetrieveSemanticDescriptionFieldStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_ArtifactByEmailStatusTest extends \Tuleap\Test\PHPUnit\TestCase //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    private Tracker&MockObject $tracker;
    private MailGatewayConfig&MockObject $tracker_plugin_conf;

    protected function setUp(): void
    {
        $this->tracker             = $this->createMock(Tracker::class);
        $this->tracker_plugin_conf = $this->createMock(MailGatewayConfig::class);
    }

    public function testItDoesNotAcceptArtifactByInsecureEmailWhenSemanticTitleIsNotDefined(): void
    {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn(null);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItDoesNotAcceptArtifactByInsecureEmailWhenSemanticDescriptionIsNotDefined(): void
    {
        $field_title = $this->createMock(\Tracker_FormElement_Field_String::class);

        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn($field_title);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());

        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItAcceptsArtifactByInsecureEmailWhenSemanticIsDefined(): void
    {
        $field_title       = $this->createMock(\Tracker_FormElement_Field_String::class);
        $field_description = $this->createMock(\Tracker_FormElement_Field_Text::class);

        $field_title->method('isRequired')->willReturn(false);
        $field_description->method('isRequired')->willReturn(false);

        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn($field_title);
        $this->tracker->method('getFormElementFields')->willReturn([$field_title, $field_description]);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withTextField($field_description));

        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItAcceptsArtifactByInsecureEmailWhenRequiredFieldsAreValid(): void
    {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $field_title       = $this->createMock(\Tracker_FormElement_Field_String::class);
        $field_description = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $field_title->method('isRequired')->willReturn(false);
        $field_description->method('isRequired')->willReturn(false);

        $this->tracker->method('getTitleField')->willReturn($field_title);
        $this->tracker->method('getFormElementFields')->willReturn([$field_title, $field_description]);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withTextField($field_description));
        $this->assertTrue($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItDoesNotAcceptArtifactByInsecureEmailWhenRequiredFieldsAreInvalid(): void
    {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $field_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $field_title->method('getId')->willReturn(1);
        $field_title->method('isRequired')->willReturn(false);
        $this->tracker->method('getTitleField')->willReturn($field_title);

        $field_description = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $field_description->method('getId')->willReturn(2);
        $field_description->method('isRequired')->willReturn(false);

        $another_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $another_field->method('getId')->willReturn(3);
        $another_field->method('isRequired')->willReturn(true);
        $this->tracker->method('getFormElementFields')->willReturn([$field_title, $another_field, $field_description]);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withTextField($field_description));
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItDoesNotCreateArtifactInTokenMode(): void
    {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canCreateArtifact($this->tracker));
    }

    public function testItUpdatesArtifactInTokenMode(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsDisabled(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItUpdatesArtifactInTokenModeWhenMailGatewayIsInsecure(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInTokenModeWhenMailGatewayIsInsecureAndTrackerDisallowEmailGateway(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInTokenMode($this->tracker));
    }

    public function testItUpdatesArtifactInInsecureMode(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertTrue($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInInsecureModeWhenTrackerEmailGatewayIsDisabled(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(false);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    public function testItDoesNotUpdateArtifactInInsecureModeWhenTokenModeIsEnabled(): void
    {
        $this->tracker_plugin_conf->method('isTokenBasedEmailgatewayEnabled')->willReturn(true);
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(false);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(false);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());
        $this->assertFalse($tracker_artifactbyemailstatus->canUpdateArtifactInInsecureMode($this->tracker));
    }

    /**
     * @testWith [false, false, false, true]
     *           [true, true, false, true]
     *           [true, true, true, false]
     */
    public function testItChecksFieldValidity(
        bool $is_title_required,
        bool $is_description_required,
        bool $is_another_field_required,
        bool $expected,
    ): void {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);

        $field_title = $this->createMock(\Tracker_FormElement_Field_String::class);
        $field_title->method('getId')->willReturn(1);
        $field_title->method('isRequired')->willReturn($is_title_required);

        $field_description = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $field_description->method('getId')->willReturn(2);
        $field_description->method('isRequired')->willReturn($is_description_required);

        $another_field = $this->createMock(\Tracker_FormElement_Field_Text::class);
        $another_field->method('getId')->willReturn(3);
        $another_field->method('isRequired')->willReturn($is_another_field_required);

        $this->tracker->method('getTitleField')->willReturn($field_title);
        $this->tracker->method('getFormElementFields')->willReturn([$field_title, $another_field, $field_description]);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withTextField($field_description));
        self::assertSame($expected, $tracker_artifactbyemailstatus->isRequiredFieldsConfigured($this->tracker));
    }

    public function testIsSemanticConfiguredReturnsFalseIfNoTitle(): void
    {
        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn(null);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());

        self::assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));
    }

    public function testIsSemanticConfiguredReturnsFalseIfNoDescription(): void
    {
        $field_title = $this->createMock(\Tracker_FormElement_Field_String::class);

        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn($field_title);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withNoField());

        self::assertFalse($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));
    }

    public function testIsSemanticConfiguredReturnsTrue(): void
    {
        $field_title       = $this->createMock(\Tracker_FormElement_Field_String::class);
        $field_description = $this->createMock(\Tracker_FormElement_Field_Text::class);


        $this->tracker_plugin_conf->method('isInsecureEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('isEmailgatewayEnabled')->willReturn(true);
        $this->tracker->method('getTitleField')->willReturn($field_title);

        $tracker_artifactbyemailstatus = new Tracker_ArtifactByEmailStatus($this->tracker_plugin_conf, RetrieveSemanticDescriptionFieldStub::withTextField($field_description));

        self::assertTrue($tracker_artifactbyemailstatus->isSemanticConfigured($this->tracker));
    }
}
