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

class UserXMLExporterTest extends TuleapTestCase {

    private $collection;
    private $user_xml_exporter;
    private $user_manager;
    private $base_xml;

    public function setUp() {
        parent::setUp();

        $this->user_manager      = mock('UserManager');
        $this->collection        = mock('UserXMLExportedCollection');
        $this->user_xml_exporter = new UserXMLExporter($this->user_manager, $this->collection);
        $this->base_xml          = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <whatever />'
        );
    }

    public function itExportsUserInXML() {
        $user = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');

        $this->assertEqual((string) $this->base_xml->user['format'], 'ldap');
        $this->assertEqual((string) $this->base_xml->user, 'ldap_01');
    }

    public function itExportsUserInXMLWithTheDefinedChildName() {
        $user = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'mychildname');

        $this->assertEqual((string) $this->base_xml->mychildname['format'], 'ldap');
        $this->assertEqual((string) $this->base_xml->mychildname, 'ldap_01');
    }

    public function itExportsUserInXMLWithUserNameIfNoLdapId() {
        $user = aUser()->withId(101)->withUserName('user_01')->build();

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');

        $this->assertEqual((string) $this->base_xml->user['format'], 'username');
        $this->assertEqual((string) $this->base_xml->user, 'user_01');
    }

    public function itExportsUserInXMLByItsId() {
        $user = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        stub($this->user_manager)->getUserById(101)->returns($user);

        $this->user_xml_exporter->exportUserByUserId(101, $this->base_xml, 'user');

        $this->assertEqual((string) $this->base_xml->user['format'], 'ldap');
        $this->assertEqual((string) $this->base_xml->user, 'ldap_01');
    }

    public function itExportsEmailInXML() {
        $this->user_xml_exporter->exportUserByMail('email@example.com', $this->base_xml, 'user');

        $this->assertEqual((string) $this->base_xml->user['format'], 'email');
        $this->assertEqual((string) $this->base_xml->user, 'email@example.com');
    }

    public function itCollectsUser() {
        $user = aUser()->withId(101)->withUserName('user_01')->build();

        expect($this->collection)->add($user)->once();

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');
    }

    public function itCollectsUserById() {
        $user = aUser()->withId(101)->withLdapId('ldap_01')->withUserName('user_01')->build();
        stub($this->user_manager)->getUserById(101)->returns($user);

        expect($this->collection)->add($user)->once();

        $this->user_xml_exporter->exportUserByUserId(101, $this->base_xml, 'user');
    }

    public function itDoesNotCollectUserByMail() {
        expect($this->collection)->add()->never();

        $this->user_xml_exporter->exportUserByMail('email@example.com', $this->base_xml, 'user');
    }
}
