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

declare(strict_types=1);

namespace User\XML\Import;

use PFUser;

final class MappingTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /** @var Mapping */
    private $mapping;

    /** @var PFUser */
    private $my_user;

    /** @var PFUser */
    private $none_user;

    /** @var PFUser */
    private $user_manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->my_user      = new \PFUser(['user_id' => 101, 'language_id' => 'en']);
        $this->none_user    = new \PFUser(['user_id' => 100, 'language_id' => 'en']);
        $this->collection   = \Mockery::spy(\User\XML\Import\ReadyToBeImportedUsersCollection::class);
        $this->user_manager = \Mockery::spy(\UserManager::class);

        $this->user_manager->shouldReceive('getUserAnonymous')->andReturns(new \PFUser(['user_id' => 0, 'language_id' => 'en']));
        $this->user_manager->shouldReceive('getUserByUserName')->with('None')->andReturns($this->none_user);

        $this->mapping = new Mapping($this->user_manager, $this->collection, \Mockery::spy(\Psr\Log\LoggerInterface::class));
    }

    public function testItReturnsAUserReferencedById(): void
    {
        $this->collection->shouldReceive('getUserById')->with(107)->andReturns(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAUserReferencedByLdapId(): void
    {
        $this->collection->shouldReceive('getUserByLdapId')->with('107')->andReturns(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="ldap">107</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAUserReferencedByUsername(): void
    {
        $this->collection->shouldReceive('getUserByUserName')->with('jdoe')->andReturns(new WillBeMappedUser('jdoe', $this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">jdoe</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnAnonymousUserReferencedByEmail(): void
    {
        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">jdoe@example.com</submitted_by>
        ');

        $user = $this->mapping->getUser($xml);

        $this->assertTrue($user->isAnonymous());
    }

    public function testItReturnsAMatchingUserReferencedByEmail(): void
    {
        $this->user_manager->shouldReceive('getUserByEmail')->with('existing@example.com')->andReturns($this->my_user);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="email">existing@example.com</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsACreatedUser(): void
    {
        $this->collection->shouldReceive('getUserById')->with(107)->andReturns(new WillBeCreatedUser('jdoe', 'John Doe', 'jdoe@example.com', 'S', 'ed107'));
        $this->user_manager->shouldReceive('getUserByUserName')->with('jdoe')->andReturns($this->my_user);

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnAlreadyExistingUser(): void
    {
        $this->collection->shouldReceive('getUserById')->with(107)->andReturns(new AlreadyExistingUser($this->my_user, 107, 'ldap1234'));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsAnActivatedUser(): void
    {
        $this->collection->shouldReceive('getUserById')->with(107)->andReturns(new WillBeActivatedUser($this->my_user));

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="id">107</submitted_by>
        ');

        $this->assertEquals(
            $this->my_user,
            $this->mapping->getUser($xml),
        );
    }

    public function testItReturnsNoneUser(): void
    {
        $this->collection->shouldReceive('getUserByUserName')->with('None')->andThrows(new UserNotFoundException());

        $xml = simplexml_load_string('<?xml version="1.0" encoding="UTF-8"?>
            <submitted_by format="username">None</submitted_by>
        ');

        $this->assertEquals(
            $this->none_user,
            $this->mapping->getUser($xml),
        );
    }
}
