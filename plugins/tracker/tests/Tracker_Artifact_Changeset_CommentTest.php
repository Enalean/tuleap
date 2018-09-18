<?php
/**
 * Copyright (c) Enalean, 2015-2018. All Rights Reserved.
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

class Tracker_Artifact_Changeset_CommentTest extends TuleapTestCase {

    private $user_manager;

    public function setUp() {
        parent::setUp();

        $user            = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $this->changeset = aChangeset()->build();
        $this->timestamp = '1433863107';

        $this->user_manager = stub('UserManager')->getUserById(101)->returns($user);
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown() {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function itExportsToXML() {
        $body = '<b> My comment 01</b>';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1, $this->changeset, 0, 0, 101, $this->timestamp, $body, 'html', 0
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, mock('UserXMLExportedCollection'));

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEqual((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEqual($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEqual((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEqual($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEqual((string) $changeset_node->comment->body, $body);
        $this->assertEqual($changeset_node->comment->body['format'], 'html');
    }

    public function itExportsToXMLWithCrossReferencesEscaped() {
        $body         = 'See art #290';
        $escaped_body = 'See art # 290';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1, $this->changeset, 0, 0, 101, $this->timestamp, $body, 'html', 0
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node    = new SimpleXMLElement($xml);
        $user_xml_exporter = new UserXMLExporter($this->user_manager, mock('UserXMLExportedCollection'));

        $comment->exportToXML($changeset_node, $user_xml_exporter);

        $this->assertNotNull($changeset_node->comment);
        $this->assertNotNull($changeset_node->comment->submitted_by);
        $this->assertNotNull($changeset_node->comment->submitted_on);
        $this->assertNotNull($changeset_node->comment->body);

        $this->assertEqual((string) $changeset_node->comment->submitted_by, 'ldap_01');
        $this->assertEqual($changeset_node->comment->submitted_by['format'], 'ldap');

        $this->assertEqual((string) $changeset_node->comment->submitted_on, '2015-06-09T17:18:27+02:00');
        $this->assertEqual($changeset_node->comment->submitted_on['format'], 'ISO8601');

        $this->assertEqual((string) $changeset_node->comment->body, $escaped_body);
        $this->assertEqual($changeset_node->comment->body['format'], 'html');
    }

    public function testCheckCommentFormat()
    {
        $this->assertIdentical('text', Tracker_Artifact_Changeset_Comment::checkCommentFormat('text'));
        $this->assertIdentical('html', Tracker_Artifact_Changeset_Comment::checkCommentFormat('html'));
        $this->assertIdentical('text', Tracker_Artifact_Changeset_Comment::checkCommentFormat('not_valid'));
        $this->assertIdentical('text', Tracker_Artifact_Changeset_Comment::checkCommentFormat(true));
    }
}