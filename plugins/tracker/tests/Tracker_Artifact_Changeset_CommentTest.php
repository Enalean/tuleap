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

class Tracker_Artifact_Changeset_CommentTest extends TuleapTestCase {

    private $user_manager;

    public function setUp() {
        parent::setUp();

        $this->user_manager = mock('UserManager');
        UserManager::setInstance($this->user_manager);
    }

    public function tearDown() {
        UserManager::clearInstance();

        parent::tearDown();
    }

    public function itExportsToXML() {
        $user      = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        $changeset = aChangeset()->build();
        $timestamp = '1433863107';
        $body      = '<b> My comment 01</b>';

        $comment = new Tracker_Artifact_Changeset_Comment(
            1, $changeset, 0, 0, 101, $timestamp, $body, 'html', 0
        );

        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <comments/>';

        $changeset_node = new SimpleXMLElement($xml);

        stub($this->user_manager)->getUserById(101)->returns($user);

        $comment->exportToXML($changeset_node);

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
}