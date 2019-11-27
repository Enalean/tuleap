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
namespace User\XML\Import;

use TuleapTestCase;
use PFUser;

class ReadyToBeImportedUsersCollection_getUserByXxxTest extends TuleapTestCase
{

    /** @var UsersToBeImportedCollection */
    private $collection;

    public function setUp()
    {
        parent::setUp();
        $this->setUpGlobalsMockery();

        $this->user = \Mockery::spy(\User\XML\Import\ReadyToBeImportedUser::class);

        $this->id       = 107;
        $this->username = 'jdoe';
        $this->ldap_id  = 'jd2398';

        $this->collection = new ReadyToBeImportedUsersCollection();
        $this->collection->add($this->user, $this->id, $this->username, $this->ldap_id);
    }

    public function itRetrievesUserByUserName()
    {
        $this->assertEqual(
            $this->collection->getUserByUserName($this->username),
            $this->user
        );
    }

    public function itThrowsAnExceptionWhenUsernameNotFound()
    {
        $this->expectException('User\XML\Import\UserNotFoundException');

        $this->collection->getUserByUserName('unknown');
    }

    public function itRetrievesUserById()
    {
        $this->assertEqual(
            $this->collection->getUserById($this->id),
            $this->user
        );
    }

    public function itThrowsAnExceptionWhenIdNotFound()
    {
        $this->expectException('User\XML\Import\UserNotFoundException');

        $this->collection->getUserById(66);
    }

    public function itRetrievesUserByLdapId()
    {
        $this->assertEqual(
            $this->collection->getUserByLdapId($this->ldap_id),
            $this->user
        );
    }

    public function itThrowsAnExceptionWhenLdapIdNotFound()
    {
        $this->expectException('User\XML\Import\UserNotFoundException');

        $this->collection->getUserById('unknown');
    }

    public function itDoesNotIndexByLdapIdWhenNoLdapId()
    {
        $user = \Mockery::spy(\User\XML\Import\ReadyToBeImportedUser::class);

        $id       = 108;
        $username = 'cstevens';
        $ldap_id  = '';

        $this->collection->add($user, $id, $username, $ldap_id);

        $this->expectException('User\XML\Import\UserNotFoundException');

        $this->collection->getUserByLdapId($ldap_id);
    }
}
