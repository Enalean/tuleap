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

class UserXMLExportedCollectionTest extends TuleapTestCase
{

    private $collection;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();
        ForgeConfig::store();
        ForgeConfig::set('tuleap_dir', __DIR__.'/../../../../../');

        $this->a_user = aUser()
            ->withId(101)
            ->withUserName('kshen')
            ->withRealName('Kool Shen')
            ->withEmail('kshen@hotmail.fr')
            ->withLdapId('cb9867')
            ->build();

        $this->another_user = aUser()
            ->withId(102)
            ->withUserName('jstar')
            ->withRealName('Joeystarr <script>')
            ->withEmail('jstar@caramail.com')
            ->build();

        $this->none = aUser()
            ->withId(100)
            ->build();

        $this->collection = new UserXMLExportedCollection(
            new XML_RNGValidator(),
            new XML_SimpleXMLCDATAFactory()
        );
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itExportsAUser()
    {
        $this->collection->add($this->a_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertNotNull($xml_object->user);
        $this->assertEqual((int)$xml_object->user[0]->id, 101);
        $this->assertEqual((string)$xml_object->user[0]->username, 'kshen');
        $this->assertEqual((string)$xml_object->user[0]->realname, 'Kool Shen');
        $this->assertEqual((string)$xml_object->user[0]->email, 'kshen@hotmail.fr');
        $this->assertEqual((string)$xml_object->user[0]->ldapid, 'cb9867');
    }

    public function itExportsMoreThanOneUser()
    {
        $this->collection->add($this->a_user);
        $this->collection->add($this->another_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertCount($xml_object->user, 2);
    }

    public function itDoesNotExportLdapIdIfNoLdap()
    {
        $this->collection->add($this->another_user);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertEqual((string)$xml_object->user[0]->ldapid, '');
    }

    public function itDoesNotExportNone()
    {
        $this->collection->add($this->none);

        $xml_content = $this->collection->toXML();
        $xml_object  = simplexml_load_string($xml_content);

        $this->assertCount($xml_object->user, 0);
    }
}
