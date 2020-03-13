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

use ForgeConfig;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tuleap\ForgeConfigSandbox;
use UserXMLExportedCollection;
use XML_RNGValidator;
use XML_SimpleXMLCDATAFactory;

final class UserXMLExportedCollectionTest extends \PHPUnit\Framework\TestCase
{
    use MockeryPHPUnitIntegration, ForgeConfigSandbox;

    private $collection;

    protected function setUp() : void
    {
        parent::setUp();
        ForgeConfig::set('tuleap_dir', __DIR__ . '/../../../../../');

        $this->a_user = new PFUser(
            [
                'user_id'     => 101,
                'user_name'   => 'kshen',
                'realname'    => 'Kool Shen',
                'email'       => 'kshen@hotmail.fr',
                'ldap_id'     => 'cb9867',
                'language_id' => 'en'
            ]
        );

        $this->another_user = new PFUser(
            [
                'user_id'     => 102,
                'user_name'   => 'jstar',
                'realname'    => 'Joeystarr <script>',
                'email'       => 'jstar@caramail.com',
                'language_id' => 'en'
            ]
        );

        $this->none = new PFUser(['user_id' => 100, 'language_id' => 'en']);

        $this->collection = new UserXMLExportedCollection(
            new XML_RNGValidator(),
            new XML_SimpleXMLCDATAFactory()
        );
    }

    public function testItExportsAUser() : void
    {
        $this->collection->add($this->a_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertNotNull($xml_object->user);
        $this->assertEquals(101, (int) $xml_object->user[0]->id);
        $this->assertEquals('kshen', (string) $xml_object->user[0]->username);
        $this->assertEquals('Kool Shen', (string) $xml_object->user[0]->realname);
        $this->assertEquals('kshen@hotmail.fr', (string) $xml_object->user[0]->email);
        $this->assertEquals('cb9867', (string) $xml_object->user[0]->ldapid);
    }

    public function testItExportsMoreThanOneUser() : void
    {
        $this->collection->add($this->a_user);
        $this->collection->add($this->another_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertCount(2, $xml_object->user);
    }

    public function testItDoesNotExportLdapIdIfNoLdap() : void
    {
        $this->collection->add($this->another_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertEquals('', (string) $xml_object->user[0]->ldapid);
    }

    public function testItDoesNotExportNone() : void
    {
        $this->collection->add($this->none);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertCount(0, $xml_object->user);
    }
}
