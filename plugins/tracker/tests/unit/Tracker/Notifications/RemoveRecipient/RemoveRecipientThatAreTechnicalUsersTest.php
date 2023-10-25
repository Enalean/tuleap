<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

namespace unit\Tracker\Notifications\RemoveRecipient;

use Psr\Log\NullLogger;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Tracker\Notifications\Recipient;
use Tuleap\Tracker\Notifications\RemoveRecipient\RemoveRecipientThatAreTechnicalUsers;
use Tuleap\Tracker\Test\Builders\ChangesetTestBuilder;

final class RemoveRecipientThatAreTechnicalUsersTest extends TestCase
{
    public function testRemovesTechnicalUserFromTheRecipients(): void
    {
        $expected_users_to_keep = [
            'not_technical' => Recipient::fromUser(UserTestBuilder::anActiveUser()->withUserName('john_doe')->build()),
        ];

        $recipients = [
            ...$expected_users_to_keep,
            'technical'    => Recipient::fromUser(UserTestBuilder::anActiveUser()->withUserName('forge__test')->build()),
        ];

        $strategy = new RemoveRecipientThatAreTechnicalUsers();

        self::assertEquals(
            $expected_users_to_keep,
            $strategy->removeRecipient(new NullLogger(), ChangesetTestBuilder::aChangeset('1')->build(), $recipients, true),
        );
    }
}
