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

class MappingTest extends TuleapTestCase {

    /** @var Mapping */
    private $mapping;

    /** @var PFUser */
    private $my_user;

    /** @var PFUser */
    private $none_user;

    /** @var PFUser */
    private $user_manager;

    public function setUp() {
        parent::setUp();

        $this->my_user      = aUser()->withId(101)->build();
        $this->none_user    = aUser()->withId(100)->build();
        $this->collection   = mock('User\XML\Import\ReadyToBeImportedUsersCollection');
        $this->user_manager = mock('UserManager');

        stub($this->user_manager)->getUserAnonymous()->returns(anAnonymousUser()->build());
        stub($this->user_manager)->getUserByUserName('None')->returns($this->none_user);

        $this->mapping = new Mapping($this->user_manager, $this->collection, mock('Logger'));
    }

    public function itReturnsAUserReferencedById() {
        stub($this->collection)->getUserById(107)->returns(
            new WillBeMappedUser('jdoe', $this->my_user)
        );

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsAUserReferencedByLdapId() {
        stub($this->collection)->getUserByLdapId('107')->returns(
            new WillBeMappedUser('jdoe', $this->my_user)
        );

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="ldap">107</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsAUserReferencedByUsername() {
        stub($this->collection)->getUserByUserName('jdoe')->returns(
            new WillBeMappedUser('jdoe', $this->my_user)
        );

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">jdoe</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsAnAnonymousUserReferencedByEmail() {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">jdoe@example.com</submitted_by>
        ');

        $user = $this->mapping->getUser($xml);

        $this->assertTrue($user->isAnonymous());
    }

    public function itReturnsAMatchingUserReferencedByEmail() {
        stub($this->user_manager)->getUserByEmail('existing@example.com')->returns($this->my_user);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">existing@example.com</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsACreatedUser() {
        stub($this->collection)->getUserById(107)->returns(
            new WillBeCreatedUser('jdoe', 'John Doe', 'jdoe@example.com', 'S', 'ed107')
        );
        stub($this->user_manager)->getUserByUserName('jdoe')->returns($this->my_user);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsAnAlreadyExistingUser() {
        stub($this->collection)->getUserById(107)->returns(
            new AlreadyExistingUser($this->my_user, 107, 'ldap1234')
        );

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsAnActivatedUser() {
        stub($this->collection)->getUserById(107)->returns(
            new WillBeActivatedUser($this->my_user)
        );

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->my_user
        );
    }

    public function itReturnsNoneUser() {
        stub($this->collection)->getUserByUserName('None')->throws(new UserNotFoundException());

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">None</submitted_by>
        ');

        $this->assertEqual(
            $this->mapping->getUser($xml),
            $this->none_user
        );
    }
}
