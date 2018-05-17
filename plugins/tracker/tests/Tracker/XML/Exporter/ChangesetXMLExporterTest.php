<?php
/**
 * Copyright (c) Enalean, 2014 - 2016. All Rights Reserved.
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
require_once __DIR__.'/../../../bootstrap.php';

class Tracker_XML_Exporter_ChangesetXMLExporterTest extends TuleapTestCase {

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

    public function setUp() {
        parent::setUp();
        $this->user_manager      = mock('UserManager');
        $this->user_xml_exporter = partial_mock(
            'UserXMLExporter',
            array('exportUserByMail', 'exportUserByUserId'),
            array($this->user_manager, mock('UserXMLExportedCollection'))
        );
        $this->artifact_xml      = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><artifact />');
        $this->values_exporter   = mock('Tracker_XML_Exporter_ChangesetValuesXMLExporter');
        $this->exporter          = new Tracker_XML_Exporter_ChangesetXMLExporter(
            $this->values_exporter,
            $this->user_xml_exporter
        );

        $changeset = mock('Tracker_Artifact_Changeset');

        $this->int_changeset_value   = new Tracker_Artifact_ChangesetValue_Integer('*', $changeset, '*', '*', '*');
        $this->float_changeset_value = new Tracker_Artifact_ChangesetValue_Float('*', $changeset, '*', '*', '*');
        $this->values = array(
            $this->int_changeset_value,
            $this->float_changeset_value
        );

        $this->artifact  = mock('Tracker_Artifact');
        $this->changeset = mock('Tracker_Artifact_Changeset');
        $this->comment   = mock('Tracker_Artifact_Changeset_Comment');

        stub($this->changeset)->getValues()->returns($this->values);
        stub($this->changeset)->getArtifact()->returns($this->artifact);
        stub($this->changeset)->getComment()->returns($this->comment);
        stub($this->changeset)->getSubmittedBy()->returns(101);
    }

    public function itAppendsChangesetNodeToArtifactNode() {
        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);

        $this->assertEqual(count($this->artifact_xml->changeset), 1);
        $this->assertEqual(count($this->artifact_xml->changeset->submitted_by), 1);
        $this->assertEqual(count($this->artifact_xml->changeset->submitted_on), 1);
    }

    public function itDelegatesTheExportOfValues() {
        expect($this->values_exporter)->exportSnapshot($this->artifact_xml, '*', $this->artifact, $this->values)->once();
        expect($this->comment)->exportToXML()->never();

        $this->exporter->exportWithoutComments($this->artifact_xml, $this->changeset);
    }

    public function itExportsTheComments() {
        $user = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        stub($this->user_manager)->getUserById(101)->returns($user);

        expect($this->values_exporter)->exportChangedFields($this->artifact_xml, '*', $this->artifact, $this->values)->once();
        expect($this->comment)->exportToXML()->once();

        $this->exporter->exportFullHistory($this->artifact_xml, $this->changeset);
    }

    public function itExportsAnonUser() {
        expect($this->user_xml_exporter)->exportUserByMail()->once();

        $changeset = stub('Tracker_Artifact_Changeset')->getValues()->returns(array());
        stub($changeset)->getSubmittedBy()->returns(null);
        stub($changeset)->getEmail()->returns('veloc@dino.com');
        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }

    public function itRemovesNullValuesInChangesetValues() {
        $value = mock('Tracker_Artifact_ChangesetValue');

        expect($this->values_exporter)->exportChangedFields('*', '*', '*', array(101 => $value))->once();

        $changeset = stub('Tracker_Artifact_Changeset')->getValues()->returns(array(
            101 => $value,
            102 => null
        ));

        $this->exporter->exportFullHistory($this->artifact_xml, $changeset);
    }
}