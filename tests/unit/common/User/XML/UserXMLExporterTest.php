<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

namespace Tuleap\User\XML;

use PFUser;
use SimpleXMLElement;
use UserXMLExporter;

final class UserXMLExporterTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private $collection;
    private $user_xml_exporter;
    private $user_manager;
    private $base_xml;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user_manager      = $this->createMock(\UserManager::class);
        $this->collection        = $this->createMock(\UserXMLExportedCollection::class);
        $this->user_xml_exporter = new UserXMLExporter($this->user_manager, $this->collection);
        $this->base_xml          = new SimpleXMLElement(
            '<?xml version="1.0" encoding="UTF-8"?>
             <whatever />'
        );
    }

    public function testItExportsUserInXML(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'ldap_id' => 'ldap_01', 'language_id' => 'en']);

        $this->collection->method('add');

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');

        self::assertEquals('ldap', (string) $this->base_xml->user['format']);
        self::assertEquals('ldap_01', (string) $this->base_xml->user);
    }

    public function testItExportsUserInXMLWithTheDefinedChildName(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'ldap_id' => 'ldap_01', 'language_id' => 'en']);

        $this->collection->method('add');

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'mychildname');

        self::assertEquals('ldap', (string) $this->base_xml->mychildname['format']);
        self::assertEquals('ldap_01', (string) $this->base_xml->mychildname);
    }

    public function testItExportsUserInXMLWithUserNameIfNoLdapId(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'language_id' => 'en']);

        $this->collection->method('add');

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');

        self::assertEquals('username', (string) $this->base_xml->user['format']);
        self::assertEquals('user_01', (string) $this->base_xml->user);
    }

    public function testItExportsUserInXMLByItsId(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'ldap_id' => 'ldap_01', 'language_id' => 'en']);

        $this->collection->method('add');
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);

        $this->user_xml_exporter->exportUserByUserId(101, $this->base_xml, 'user');

        self::assertEquals('ldap', (string) $this->base_xml->user['format']);
        self::assertEquals('ldap_01', (string) $this->base_xml->user);
    }

    public function testItExportsEmailInXML(): void
    {
        $this->user_xml_exporter->exportUserByMail('email@example.com', $this->base_xml, 'user');

        self::assertEquals('email', (string) $this->base_xml->user['format']);
        self::assertEquals('email@example.com', (string) $this->base_xml->user);
    }

    public function testItCollectsUser(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'language_id' => 'en']);

        $this->collection->expects(self::once())->method('add')->with($user);

        $this->user_xml_exporter->exportUser($user, $this->base_xml, 'user');
    }

    public function testItCollectsUserById(): void
    {
        $user = new PFUser(['user_id' => 101, 'user_name' => 'user_01', 'ldap_id' => 'ldap_01', 'language_id' => 'en']);
        $this->user_manager->method('getUserById')->with(101)->willReturn($user);

        $this->collection->expects(self::once())->method('add')->with($user);

        $this->user_xml_exporter->exportUserByUserId(101, $this->base_xml, 'user');
    }

    public function testItDoesNotCollectUserByMail(): void
    {
        $this->collection->expects(self::never())->method('add');

        $this->user_xml_exporter->exportUserByMail('email@example.com', $this->base_xml, 'user');
    }
}
