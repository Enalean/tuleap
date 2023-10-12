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

namespace User\XML\Import;

use PHPUnit\Framework\MockObject\MockObject;

final class ReadyToBeImportedUsersCollectionTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ReadyToBeImportedUsersCollection $collection;
    /**
     * @var MockObject&ReadyToBeImportedUser
     */
    private $user;
    private int $id;
    private string $username;
    private string $ldap_id;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = $this->createMock(\User\XML\Import\ReadyToBeImportedUser::class);

        $this->id       = 107;
        $this->username = 'jdoe';
        $this->ldap_id  = 'jd2398';

        $this->collection = new ReadyToBeImportedUsersCollection();
        $this->collection->add($this->user, $this->id, $this->username, $this->ldap_id);
    }

    public function testItRetrievesUserByUserName(): void
    {
        self::assertEquals(
            $this->user,
            $this->collection->getUserByUserName($this->username),
        );
    }

    public function testItThrowsAnExceptionWhenUsernameNotFound(): void
    {
        $this->expectException(\User\XML\Import\UserNotFoundException::class);

        $this->collection->getUserByUserName('unknown');
    }

    public function testItRetrievesUserById(): void
    {
        self::assertEquals(
            $this->collection->getUserById($this->id),
            $this->user
        );
    }

    public function testItThrowsAnExceptionWhenIdNotFound(): void
    {
        $this->expectException(\User\XML\Import\UserNotFoundException::class);

        $this->collection->getUserById(66);
    }

    public function testItRetrievesUserByLdapId(): void
    {
        self::assertEquals(
            $this->user,
            $this->collection->getUserByLdapId($this->ldap_id),
        );
    }

    public function testItThrowsAnExceptionWhenLdapIdNotFound(): void
    {
        $this->expectException(\User\XML\Import\UserNotFoundException::class);

        $this->collection->getUserById('unknown');
    }

    public function testItDoesNotIndexByLdapIdWhenNoLdapId(): void
    {
        $user = $this->createMock(\User\XML\Import\ReadyToBeImportedUser::class);

        $id       = 108;
        $username = 'cstevens';
        $ldap_id  = '';

        $this->collection->add($user, $id, $username, $ldap_id);

        $this->expectException(\User\XML\Import\UserNotFoundException::class);

        $this->collection->getUserByLdapId($ldap_id);
    }
}
