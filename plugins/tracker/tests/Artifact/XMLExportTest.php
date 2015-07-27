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

require_once TRACKER_BASE_DIR . '/../tests/bootstrap.php';

class Tracker_Artifact_XMLExportTest extends TuleapTestCase {

    private $user_manager;
    private $formelement_factory;

    public function setUp() {
        parent::setUp();

        $this->user_manager = mock('UserManager');
        UserManager::setInstance($this->user_manager);

        $this->formelement_factory = mock('Tracker_FormElementFactory');
        Tracker_FormElementFactory::setInstance($this->formelement_factory);
    }

    public function tearDown() {
        UserManager::clearInstance();
        Tracker_FormElementFactory::clearInstance();

        parent::tearDown();
    }

    public function itExportsArtifactsInXML() {
        $user_01 = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $user_02 = aUser()->withId(102)->withLdapId('ldap_02')->withUserName('user_02')->build();

        stub($this->user_manager)->getUserById(101)->returns($user_01);
        stub($this->user_manager)->getUserById(102)->returns($user_02);

        stub($this->formelement_factory)->getUsedFileFields()->returns(array());

        $project = stub('Project')->getID()->returns(101);
        $tracker = aTracker()->withId(101)->withProject($project)->build();

        $timestamp_01 = '1433863107';
        $timestamp_02 = '1433949507';
        $timestamp_03 = '1434035907';
        $timestamp_04 = '1434122307';

        $text_field_01 = stub('Tracker_FormElement_Field_Text')->getName()->returns('text_01');
        stub($text_field_01)->getTracker()->returns($tracker);
        $text_field_02 = stub('Tracker_FormElement_Field_Text')->getName()->returns('text_02');
        stub($text_field_02)->getTracker()->returns($tracker);

        $value_01 = new Tracker_Artifact_ChangesetValue_Text(1, $text_field_01, true, 'value_01', 'text');
        $value_02 = new Tracker_Artifact_ChangesetValue_Text(2, $text_field_01, true, 'value_02', 'text');
        $value_03 = new Tracker_Artifact_ChangesetValue_Text(3, $text_field_01, true, 'value_03', 'text');
        $value_04 = new Tracker_Artifact_ChangesetValue_Text(4, $text_field_01, true, 'value_04', 'text');
        $value_05 = new Tracker_Artifact_ChangesetValue_Text(5, $text_field_02, true, 'value_05', 'text');
        $value_06 = new Tracker_Artifact_ChangesetValue_Text(6, $text_field_02, true, 'value_06', 'text');
        $value_07 = new Tracker_Artifact_ChangesetValue_Text(7, $text_field_02, true, 'value_07', 'text');

        $changeset_01 = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getSubmittedBy',
                'getSubmittedOn',
                'getValues',
                'getArtifact',
                'getComment'
            )
        );

        $changeset_02 = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getSubmittedBy',
                'getSubmittedOn',
                'getValues',
                'getArtifact',
                'getComment'
            )
        );

        $changeset_03 = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getSubmittedBy',
                'getSubmittedOn',
                'getValues',
                'getArtifact',
                'getComment'
            )
        );

        $changeset_04 = partial_mock(
            'Tracker_Artifact_Changeset',
            array(
                'getId',
                'getSubmittedBy',
                'getSubmittedOn',
                'getValues',
                'getArtifact',
                'getComment'
            )
        );

        stub($changeset_01)->getSubmittedBy()->returns(101);
        stub($changeset_01)->getSubmittedOn()->returns($timestamp_01);
        stub($changeset_01)->getValues()->returns(array($value_01, $value_02));

        stub($changeset_02)->getSubmittedBy()->returns(101);
        stub($changeset_02)->getSubmittedOn()->returns($timestamp_02);
        stub($changeset_02)->getValues()->returns(array($value_03, $value_04));

        stub($changeset_03)->getSubmittedBy()->returns(101);
        stub($changeset_03)->getSubmittedOn()->returns($timestamp_03);
        stub($changeset_03)->getValues()->returns(array($value_05, $value_06));

        stub($changeset_04)->getSubmittedBy()->returns(102);
        stub($changeset_04)->getSubmittedOn()->returns($timestamp_04);
        stub($changeset_04)->getValues()->returns(array($value_07));

        $artifact_01 = anArtifact()->withTracker($tracker)->withId(101)->withChangesets(array($changeset_01, $changeset_02))->build();
        $artifact_02 = anArtifact()->withTracker($tracker)->withId(102)->withChangesets(array($changeset_03, $changeset_04))->build();

        stub($changeset_01)->getArtifact()->returns($artifact_01);
        stub($changeset_02)->getArtifact()->returns($artifact_01);
        stub($changeset_03)->getArtifact()->returns($artifact_02);
        stub($changeset_04)->getArtifact()->returns($artifact_02);

        $comment_01 = new Tracker_Artifact_Changeset_Comment(
            1, $changeset_01, 0, 0, 101, $timestamp_01, '<b> My comment 01</b>', 'html', 0
        );

        $comment_02 = new Tracker_Artifact_Changeset_Comment(
            2, $changeset_02, 0, 0, 101, $timestamp_02, '<b> My comment 02</b>', 'html', 0
        );

        $comment_03 = new Tracker_Artifact_Changeset_Comment(
            3, $changeset_03, 0, 0, 102, $timestamp_03, '<b> My comment 03</b>', 'html', 0
        );

        $comment_04 = new Tracker_Artifact_Changeset_Comment(
            4, $changeset_04, 0, 0, 102, $timestamp_04, '<b> My comment 04</b>', 'html', 0
        );

        stub($changeset_01)->getComment()->returns($comment_01);
        stub($changeset_02)->getComment()->returns($comment_02);
        stub($changeset_03)->getComment()->returns($comment_03);
        stub($changeset_04)->getComment()->returns($comment_04);

        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = stub('Tracker_ArtifactFactory')
            ->getArtifactsByTrackerId(101)
            ->returns(array(
                $artifact_01,
                $artifact_02
            ));
        $can_bypass_threshold = true;

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            $can_bypass_threshold
        );

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $admin_user = stub('PFUser')->isSuperUser()->returns(true);

        $archive = new ZipArchive();

        $exporter->export($tracker, $xml_element, $admin_user, $archive);

        $this->assertNotNull($xml_element->artifacts);

        $this->assertEqual((string) $xml_element->artifacts->artifact[0]['id'], '101');
        $this->assertEqual((string) $xml_element->artifacts->artifact[1]['id'], '102');

        $this->assertNotNull($xml_element->artifacts->artifact[0]->changeset);
        $this->assertCount($xml_element->artifacts->artifact[0]->changeset, 2);
        $this->assertNotNull($xml_element->artifacts->artifact[1]->changeset);
        $this->assertCount($xml_element->artifacts->artifact[1]->changeset, 2);
    }
}

class Tracker_Artifact_XMLExport_forceTest extends TuleapTestCase {

    public function itRaisesAnExceptionWhenThresholdIsReached() {
        $rng_validator    = new XML_RNGValidator();
        $artifact_factory = stub('Tracker_ArtifactFactory')
            ->getArtifactsByTrackerId()
            ->returns(array_fill(0, Tracker_Artifact_XMLExport::THRESHOLD + 1, null));
        $can_bypass_threshold = false;

        $exporter = new Tracker_Artifact_XMLExport(
            $rng_validator,
            $artifact_factory,
            $can_bypass_threshold
        );

        $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                             <project />');

        $this->expectException('Tracker_Artifact_XMLExportTooManyArtifactsException');

        $archive = new ZipArchive();

        $exporter->export(mock('Tracker'), $xml_element, mock('PFUser'), $archive);
    }
}
