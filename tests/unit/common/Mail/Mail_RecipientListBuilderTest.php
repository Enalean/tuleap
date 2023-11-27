<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

namespace Tuleap\Mail;

use Mail_RecipientListBuilder;
use PFUser;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;

// phpcs:ignore Squiz.Classes.ValidClassName.NotCamelCaps
final class Mail_RecipientListBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private string $external_address           = 'toto@example.com';
    private string $external_address2          = 'toto2@example.com';
    private string $active_user_address        = 'active@tuleap.example.com';
    private string $suspended_user_address     = 'suspended@tuleap.example.com';
    private string $deleted_user_address       = 'deleted@tuleap.example.com';
    private string $bob_suspended_user_address = 'bob@tuleap.example.com';
    private string $bob_active_user_address    = 'bob@tuleap.example.com';

    private string $active_user_name        = 'Valid User';
    private string $suspended_user_name     = 'Suspended User';
    private string $deleted_user_name       = 'Deleted User';
    private string $bob_suspended_user_name = 'Suspended Bob';
    private string $bob_active_user_name    = 'Active Bob';

    private string $bob_identifier = 'bdylan';

    private PFUser $active_user;
    private PFUser $suspended_user;
    private PFUser $deleted_user;
    private PFUser $bob_suspended_user;
    private PFUser $bob_active_user;
    private \UserManager&MockObject $user_manager;

    private Mail_RecipientListBuilder $builder;

    protected function setUp(): void
    {
        parent::setUp();

        $this->active_user        = $this->buildUser(PFUser::STATUS_ACTIVE, $this->active_user_address, $this->active_user_name);
        $this->suspended_user     = $this->buildUser(PFUser::STATUS_SUSPENDED, $this->suspended_user_address, $this->suspended_user_name);
        $this->deleted_user       = $this->buildUser(PFUser::STATUS_DELETED, $this->deleted_user_address, $this->deleted_user_name);
        $this->bob_suspended_user = $this->buildUser(PFUser::STATUS_SUSPENDED, $this->bob_suspended_user_address, $this->bob_suspended_user_name);
        $this->bob_active_user    = $this->buildUser(PFUser::STATUS_ACTIVE, $this->bob_active_user_address, $this->bob_active_user_name);

        $this->user_manager = $this->createMock(\UserManager::class);

        $this->builder = new Mail_RecipientListBuilder($this->user_manager);
    }

    public function testItReturnsAnExternalAddress(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->with($this->external_address)->willReturn([]);
        $this->user_manager->method('findUser')->with($this->external_address)->willReturn(null);

        $list = $this->builder->getValidRecipientsFromAddresses([$this->external_address]);

        $expected = [
            ['email' => $this->external_address, 'real_name' => ''],
        ];

        self::assertEquals($expected, $list);
    }

    public function testItReturnsAListOfExternalAddresses(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->withConsecutive(
            [$this->external_address],
            [$this->external_address2]
        )->willReturn([]);
        $this->user_manager->method('findUser')->withConsecutive(
            [$this->external_address],
            [$this->external_address2]
        )->willReturn(null);

        $list = $this->builder->getValidRecipientsFromAddresses(
            [$this->external_address, $this->external_address2]
        );

        $expected = [
            ['email' => $this->external_address,  'real_name' => ''],
            ['email' => $this->external_address2, 'real_name' => ''],
        ];

        self::assertEquals($expected, $list);
    }

    public function testItLooksForAUser(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->with($this->active_user_address)->willReturn([$this->active_user]);

        $list = $this->builder->getValidRecipientsFromAddresses(
            [$this->active_user_address]
        );

        $expected = [
            ['email' => $this->active_user_address, 'real_name' => $this->active_user_name],
        ];

        self::assertEquals($expected, $list);
    }

    public function testItDoesNotOutputSuspendedNorDeletedUsers(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->withConsecutive(
            [$this->suspended_user_address],
            [$this->deleted_user_address]
        )->willReturnOnConsecutiveCalls([$this->suspended_user], [$this->deleted_user]);

        $list = $this->builder->getValidRecipientsFromAddresses(
            [$this->suspended_user_address, $this->deleted_user_address]
        );

        $expected = [
        ];

        self::assertEquals($expected, $list);
    }

    public function testItTakesTheFirstUserAccountWithAllowedStatus(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->with($this->bob_active_user_address)->willReturn([$this->bob_active_user]);

        $list = $this->builder->getValidRecipientsFromAddresses(
            [$this->bob_active_user_address]
        );

        $expected = [
            ['email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name],
        ];

        self::assertEquals($expected, $list);
    }

    public function testItFallbacksOnFindUserIfEmailNotFound(): void
    {
        $this->user_manager->method('getAllUsersByEmail')->with($this->bob_identifier)->willReturn([$this->bob_active_user]);

        $list = $this->builder->getValidRecipientsFromAddresses(
            [$this->bob_identifier]
        );

        $expected = [
            ['email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name],
        ];

        self::assertEquals($expected, $list);
    }

    public function testItValidatesAListOfUsers(): void
    {
        $list = $this->builder->getValidRecipientsFromUsers(
            [
                $this->active_user,
                $this->deleted_user,
                $this->bob_suspended_user,
                $this->bob_active_user,
            ]
        );

        $expected = [
            ['email' => $this->active_user_address,     'real_name' => $this->active_user_name],
            ['email' => $this->bob_active_user_address, 'real_name' => $this->bob_active_user_name],
        ];

        self::assertEquals($expected, $list);
    }

    private function buildUser(string $status, string $email, string $realname): PFUser
    {
        return UserTestBuilder::anActiveUser()
            ->withStatus($status)
            ->withEmail($email)
            ->withRealName($realname)
            ->withLocale('en')
            ->build();
    }
}
