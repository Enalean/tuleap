<?php
/**
 * Copyright (c) Enalean, 2014 - 2018. All Rights Reserved.
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

require_once __DIR__ . '/../../../bootstrap.php';

class Tracker_XML_Exporter_ChangesetXMLExporterTest extends TuleapTestCase
{

    /** @var Tracker_XML_Exporter_ChangesetXMLExporter */
    private $exporter;

    /** @var SimpleXMLElement */
    private $artifact_xml;

    /** @var Tracker_XML_Exporter_ChangesetValuesXMLExporter */
    private $values_exporter;

    /** @var Tracker_Artifact_ChangesetValue */
    private $values;

    /** @var Tracker_Artifact */
    private $artifact;
    private $user_manager;

    /** @var UserXMLExporter */
    private $user_xml_exporter;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        $this->user_manager      = \Mockery::spy(\UserManager::class);
        $this->user_xml_exporter = \Mockery::mock(
            \UserXMLExporter::class,
            [$this->user_manager, Mockery::spy(UserXMLExportedCollection::class)]
        )
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();
        $this->artifact_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->values_exporter   = \Mockery::spy(\Tracker_XML_Exporter_ChangesetValuesXMLExporter::class);
        $this->exporter          = new Tracker_XML_Exporter_ChangesetXMLExporter(
            $this->values_exporter,
            $this->user_xml_exporter
        );

        $changeset = \Mockery::spy(\Tracker_Artifact_Changeset::class);

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', $changeset, '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', $changeset, '*', '*', '*');
        $this->values = array(
            $this->int_changeset_value,
            $this->float_changeset_value
        );

        $this->artifact  = \Mockery::spy(\Tracker_Artifact::class);
        $this->changeset = \Mockery::mock(\Tracker_Artifact_Changeset::class);
        $this->comment   = \Mockery::spy(\Tracker_Artifact_Changeset_Comment::class);

        $this->changeset->shouldReceive(
            [
                'getValues'      => $this->values,
                'getArtifact'    => $this->artifact,
                'getComment'     => $this->comment,
                'getSubmittedBy' => 101,
                'getSubmittedOn' => 1234567890,
                'getId'          => 123,
            ]
        );
        $this->changeset->shouldReceive('forceFetchAllValues');
    }

    public function itAppendsChangesetNodeToArtifactNode()
    {
        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);

        $this->assertEqual(count($this->artifact_xml->changeset), 1);
        $this->assertEqual(count($this->artifact_xml->changeset->submitted_by), 1);
        $this->assertEqual(count($this->artifact_xml->changeset->submitted_on), 1);
    }

    public function itDelegatesTheExportOfValues()
    {
        expect($this->values_exporter)->exportSnapshot($this->artifact_xml, '*', $this->artifact, $this->values)->once();
        expect($this->comment)->exportToXML()->never();

        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);
    }

    public function itExportsTheComments()
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01'
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturn($user);

        expect($this->values_exporter)->exportChangedFields($this->artifact_xml, '*', $this->artifact, $this->values)->once();
        expect($this->comment)->exportToXML()->once();

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset);
    }

    public function itExportsTheIdOfTheChangeset(): void
    {
        $user = new PFUser([
            'user_id' => 101,
            'language_id' => 'en',
            'user_name' => 'user_01',
            'ldap_id' => 'ldap_01'
        ]);
        $this->user_manager->shouldReceive('getUserById')->with(101)->andReturn($user);

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset);

        $this->assertEqual((string) $this->artifact_xml->changeset['id'], 'CHANGESET_123');
    }

    public function itExportsAnonUser()
    {
        expect($this->user_xml_exporter)->exportUserByMail()->once();

        $changeset = mockery_stub(\Tracker_Artifact_Changeset::class)->getValues()->returns(array());
        stub($changeset)->getSubmittedBy()->returns(null);
        stub($changeset)->getEmail()->returns('veloc@dino.com');
        stub($changeset)->getArtifact()->returns($this->artifact);
        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }

    public function itRemovesNullValuesInChangesetValues()
    {
        $value = \Mockery::spy(\Tracker_Artifact_ChangesetValue::class);

        expect($this->values_exporter)->exportChangedFields('*', '*', '*', array(101 => $value))->once();

        $changeset = mockery_stub(\Tracker_Artifact_Changeset::class)->getValues()->returns(array(
            101 => $value,
            102 => null
        ));

        stub($changeset)->getArtifact()->returns($this->artifact);

        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }
}
