<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\Test\Widget\Management;

use PHPUnit\Framework\Attributes\TestWith;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Timetracking\Widget\Management\ViewableUserRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ViewableUserRetrieverTest extends TestCase
{
    public const ALICE_ID = 102;

    public function testItReturnsNullWhenCurrentUserIsAnonymous(): void
    {
        $current_user = UserTestBuilder::anAnonymousUser()->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus(\PFUser::STATUS_ACTIVE)
            ->build();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
        );

        self::assertNull($retriever->getViewableUser($current_user, self::ALICE_ID));
    }

    #[TestWith([\PFUser::STATUS_PENDING, \PFUser::STATUS_SUSPENDED, \PFUser::STATUS_VALIDATED, \PFUser::STATUS_VALIDATED_RESTRICTED, \PFUser::STATUS_DELETED])]
    public function testItReturnsNullWhenAliceIsNotAlive(string $status): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
        );

        self::assertNull($retriever->getViewableUser($current_user, self::ALICE_ID));
    }

    #[TestWith([\PFUser::STATUS_ACTIVE, \PFUser::STATUS_RESTRICTED])]
    public function testItReturnsAliveUser(string $status): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
        );

        self::assertNotNull($retriever->getViewableUser($current_user, self::ALICE_ID));
    }
}
