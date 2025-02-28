<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Tuleap\Tracker\Test\Builders\ArtifactTestBuilder;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter $exporter;

    private SimpleXMLElement $changeset_xml;

    private SimpleXMLElement $artifact_xml;

    private Tracker_Artifact_ChangesetValue_OpenList&MockObject $changeset_value;

    private Tracker_FormElement_Field $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $user         = new PFUser([
            'user_id' => 112,
            'language_id' => 'en',
            'ldap_id' => 'ldap_01',
        ]);
        $user_manager = $this->createMock(UserManager::class);
        $user_manager->method('getUserById')->with(112)->willReturn($user);
        $user_xml_exporter = new UserXMLExporter($user_manager, $this->createMock(UserXMLExportedCollection::class));

        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter);
    }

    private function setUpUserTests(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Users::class);
        $bind->method('getType')->willReturn('users');
        $open_value = $this->createMock(Tracker_FormElement_Field_List_OpenValue::class);
        $open_value->method('getLabel')->willReturn('email@tuleap.org');


        $this->field = $this->createMock(Tracker_FormElement_Field_OpenList::class);
        $this->field->method('getBind')->willReturn($bind);
        $this->field->method('getName')->willReturn('CC');
        $this->field->method('getOpenValueById')->willReturn($open_value);

        $this->changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->method('getField')->willReturn($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNodeUser(): void
    {
        $this->setUpUserTests();
        $this->changeset_value->method('getValue')->willReturn([
            'o14',
            'b112',
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('open_list', (string) $field_change['type']);
        $this->assertEquals('users', (string) $field_change['bind']);
        $this->assertEquals('email@tuleap.org', (string) $field_change->value[0]);
        $this->assertEquals('label', (string) $field_change->value[0]['format']);
        $this->assertEquals('ldap_01', (string) $field_change->value[1]);
        $this->assertEquals('ldap', (string) $field_change->value[1]['format']);
    }

    private function setUpUGroupsTest(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Ugroups::class);
        $bind->method('getType')->willReturn('ugroups');
        $open_value = $this->createMock(Tracker_FormElement_Field_List_OpenValue::class);
        $open_value->method('getLabel')->willReturn('new_ugroup');

        $this->field = $this->createMock(Tracker_FormElement_Field_OpenList::class);
        $this->field->method('getBind')->willReturn($bind);
        $this->field->method('getName')->willReturn('ugroup_binded');
        $this->field->method('getOpenValueById')->willReturn($open_value);

        $this->changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->method('getField')->willReturn($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNodeUGroup(): void
    {
        $this->setUpUGroupsTest();
        $this->changeset_value->method('getValue')->willReturn(['o14', 'b112']);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('open_list', (string) $field_change['type']);
        $this->assertEquals('ugroups', (string) $field_change['bind']);
        $this->assertEquals('new_ugroup', (string) $field_change->value[0]);
        $this->assertEquals('label', (string) $field_change->value[0]['format']);
        $this->assertEquals('b112', (string) $field_change->value[1]);
        $this->assertEquals('id', (string) $field_change->value[1]['format']);
    }

    private function setUpStaticTest(): void
    {
        $bind = $this->createMock(Tracker_FormElement_Field_List_Bind_Static::class);
        $bind->method('getType')->willReturn('static');
        $open_value = $this->createMock(Tracker_FormElement_Field_List_OpenValue::class);
        $open_value->method('getLabel')->willReturn('keyword01');

        $this->field = $this->createMock(Tracker_FormElement_Field_OpenList::class);
        $this->field->method('getBind')->willReturn($bind);
        $this->field->method('getName')->willReturn('keywords');
        $this->field->method('getOpenValueById')->willReturn($open_value);

        $this->changeset_value = $this->createMock(Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->method('getField')->willReturn($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNode(): void
    {
        $this->setUpStaticTest();
        $this->changeset_value->method('getValue')->willReturn(['o14', 'b112']);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            ArtifactTestBuilder::anArtifact(101)->build(),
            $this->changeset_value
        );

        $field_change = $this->changeset_xml->field_change;
        $this->assertEquals('open_list', (string) $field_change['type']);
        $this->assertEquals('static', (string) $field_change['bind']);
        $this->assertEquals('keyword01', (string) $field_change->value[0]);
        $this->assertEquals('label', (string) $field_change->value[0]['format']);
        $this->assertEquals('b112', (string) $field_change->value[1]);
        $this->assertEquals('id', (string) $field_change->value[1]['format']);
    }
}
