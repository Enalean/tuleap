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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporterTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $changeset_xml;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_Artifact_ChangesetValue_OpenList */
    private $changeset_value;

    /** @var Tracker_FormElement_Field */
    private $field;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artifact_xml  = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->changeset_xml = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><changeset />');

        $user = new PFUser([
            'user_id' => 112,
            'language_id' => 'en',
            'ldap_id' => 'ldap_01'
        ]);
        $user_manager      = \Mockery::spy(\UserManager::class)->shouldReceive('getUserById')->with(112)->andReturns($user)->getMock();
        $user_xml_exporter = new UserXMLExporter($user_manager, \Mockery::spy(\UserXMLExportedCollection::class));

        $this->exporter = new Tracker_XML_Exporter_ChangesetValue_ChangesetValueOpenListXMLExporter($user_xml_exporter);
    }

    private function setUpUserTests(): void
    {
        $bind       = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Users::class)->shouldReceive('getType')->andReturns('users')->getMock();
        $open_value = \Mockery::spy(\Tracker_FormElement_Field_List_OpenValue::class)->shouldReceive('getLabel')->andReturns('email@tuleap.org')->getMock();


        $this->field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class)->shouldReceive('getBind')->andReturns($bind)->getMock();
        $this->field->shouldReceive('getName')->andReturns('CC');
        $this->field->shouldReceive('getOpenValueById')->andReturns($open_value);

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNodeUser(): void
    {
        $this->setUpUserTests();
        $this->changeset_value->shouldReceive('getValue')->andReturns([
            'o14',
            'b112'
        ]);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
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
        $bind       = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Ugroups::class)->shouldReceive('getType')->andReturns('ugroups')->getMock();
        $open_value = \Mockery::spy(\Tracker_FormElement_Field_List_OpenValue::class)->shouldReceive('getLabel')->andReturns('new_ugroup')->getMock();

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class)->shouldReceive('getBind')->andReturns($bind)->getMock();
        $this->field->shouldReceive('getName')->andReturns('ugroup_binded');
        $this->field->shouldReceive('getOpenValueById')->andReturns($open_value);

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNodeUGroup(): void
    {
        $this->setUpUGroupsTest();
        $this->changeset_value->shouldReceive('getValue')->andReturns(['o14', 'b112']);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
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
        $bind       = \Mockery::spy(\Tracker_FormElement_Field_List_Bind_Static::class)->shouldReceive('getType')->andReturns('static')->getMock();
        $open_value = \Mockery::spy(\Tracker_FormElement_Field_List_OpenValue::class)->shouldReceive('getLabel')->andReturns('keyword01')->getMock();

        $this->field = \Mockery::spy(\Tracker_FormElement_Field_OpenList::class)->shouldReceive('getBind')->andReturns($bind)->getMock();
        $this->field->shouldReceive('getName')->andReturns('keywords');
        $this->field->shouldReceive('getOpenValueById')->andReturns($open_value);

        $this->changeset_value = \Mockery::spy(\Tracker_Artifact_ChangesetValue_OpenList::class);
        $this->changeset_value->shouldReceive('getField')->andReturns($this->field);
    }

    public function testItCreatesFieldChangeNodeWithMultipleValuesInChangesetNode(): void
    {
        $this->setUpStaticTest();
        $this->changeset_value->shouldReceive('getValue')->andReturns(['o14', 'b112']);

        $this->exporter->export(
            $this->artifact_xml,
            $this->changeset_xml,
            \Mockery::spy(\Tuleap\Tracker\Artifact\Artifact::class),
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
