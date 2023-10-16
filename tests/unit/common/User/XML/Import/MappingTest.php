<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

namespace User\XML\Import;

use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

final class MappingTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private Mapping $mapping;
    private PFUser $my_user;
    private PFUser $none_user;

    /** @var \UserManager&MockObject */
    private $user_manager;
    /**
     * @var ReadyToBeImportedUsersCollection&MockObject
     */
    private $collection;

    protected function setUp(): void
    {
        parent::setUp();

        $this->my_user      = new \PFUser(['user_id' => 101, 'language_id' => 'en']);
        $this->none_user    = new \PFUser(['user_id' => 100, 'language_id' => 'en']);
        $this->collection   = $this->createMock(ReadyToBeImportedUsersCollection::class);
        $this->user_manager = $this->createMock(\UserManager::class);

        $this->user_manager->method('getUserAnonymous')->willReturn(new \PFUser(['user_id' => 0, 'language_id' => 'en']));
        $this->user_manager->method('getUserByUserName')->willReturnMap([
            ['None', $this->none_user],
            ['jdoe', $this->my_user],
        ]);

        $this->mapping = new Mapping($this->user_manager, $this->collection, new NullLogger());
    }

    public function testItReturnsAUserReferencedById(): void
    {
        $this->collection->method('getUserById')->with(107)->willReturn(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAUserReferencedByLdapId(): void
    {
        $this->collection->method('getUserByLdapId')->with('107')->willReturn(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="ldap">107</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAUserReferencedByUsername(): void
    {
        $this->collection->method('getUserByUserName')->with('jdoe')->willReturn(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">jdoe</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnAnonymousUserReferencedByEmail(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">jdoe@example.com</submitted_by>
        ');

        $this->user_manager->method('getUserByEmail')->with('jdoe@example.com')->willReturn(null);

        $user = $this->mapping->getUser($xml);

        self::assertTrue($user->isAnonymous());
    }

    public function testItReturnsAMatchingUserReferencedByEmail(): void
    {
        $this->user_manager->method('getUserByEmail')->with('existing@example.com')->willReturn($this->my_user);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">existing@example.com</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsACreatedUser(): void
    {
        $this->collection->method('getUserById')->with(107)->willReturn(new WillBeCreatedUser('jdoe', 'John Doe', 'jdoe@example.com', 'S', 'ed107'));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnAlreadyExistingUser(): void
    {
        $this->collection->method('getUserById')->with(107)->willReturn(new AlreadyExistingUser($this->my_user, 107, 'ldap1234'));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnActivatedUser(): void
    {
        $this->collection->method('getUserById')->with(107)->willReturn(new WillBeActivatedUser($this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        self::assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsNoneUser(): void
    {
        $this->collection->method('getUserByUserName')->with('None')->willThrowException(new UserNotFoundException());

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">None</submitted_by>
        ');

        self::assertEquals(
            $this->none_user,
            $this->mapping->getUser($xml),
        );
    }
}
