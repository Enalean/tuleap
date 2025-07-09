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
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\RetrieveUserByIdStub;
use Tuleap\Timetracking\Tests\Stub\VerifyManagerCanSeeTimetrackingOfUserStub;
use Tuleap\Timetracking\Widget\Management\ViewableUserRetriever;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ViewableUserRetrieverTest extends TestCase
{
    public const ALICE_ID = 102;

    public function testItReturnsFaultWhenCurrentUserIsAnonymous(): void
    {
        $current_user = UserTestBuilder::anAnonymousUser()->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus(\PFUser::STATUS_ACTIVE)
            ->build();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::notAllowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
            $perms_verifier,
        );

        self::assertTrue(Result::isErr($retriever->getViewableUser($current_user, self::ALICE_ID)));
        self::assertFalse($perms_verifier->isCalled());
    }

    public function testCurrentUserCanViewItself(): void
    {
        $current_user = UserTestBuilder::buildWithDefaults();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::notAllowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withNoUser(),
            $perms_verifier,
        );

        $result = $retriever->getViewableUser($current_user, (int) $current_user->getId());
        self::assertTrue(Result::isOk($result));
        self::assertSame($current_user, $result->value);
        self::assertFalse($perms_verifier->isCalled());
    }

    #[TestWith([\PFUser::STATUS_PENDING])]
    #[TestWith([\PFUser::STATUS_SUSPENDED])]
    #[TestWith([\PFUser::STATUS_VALIDATED])]
    #[TestWith([\PFUser::STATUS_VALIDATED_RESTRICTED])]
    #[TestWith([\PFUser::STATUS_DELETED])]
    public function testItReturnsFaultWhenAliceIsNotAlive(string $status): void
    {
        $current_user = UserTestBuilder::anActiveUser()
            ->withSiteAdministrator()
            ->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::notAllowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
            $perms_verifier,
        );

        self::assertTrue(Result::isErr($retriever->getViewableUser($current_user, self::ALICE_ID)));
        self::assertFalse($perms_verifier->isCalled());
    }

    #[TestWith([\PFUser::STATUS_ACTIVE])]
    #[TestWith([\PFUser::STATUS_RESTRICTED])]
    public function testItReturnsAliveUserIfCurrentUserIsSuperUser(string $status): void
    {
        $current_user = UserTestBuilder::anActiveUser()
            ->withSiteAdministrator()
            ->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::allowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
            $perms_verifier,
        );

        self::assertTrue(Result::isOk($retriever->getViewableUser($current_user, self::ALICE_ID)));
        self::assertFalse($perms_verifier->isCalled());
    }

    #[TestWith([\PFUser::STATUS_ACTIVE])]
    #[TestWith([\PFUser::STATUS_RESTRICTED])]
    public function testItReturnsAliveUserIfCurrentUserIsAllowedToSeeHerTimesheeting(string $status): void
    {
        $current_user = UserTestBuilder::anActiveUser()
            ->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::allowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
            $perms_verifier,
        );

        self::assertTrue(Result::isOk($retriever->getViewableUser($current_user, self::ALICE_ID)));
        self::assertTrue($perms_verifier->isCalled());
    }

    #[TestWith([\PFUser::STATUS_ACTIVE])]
    #[TestWith([\PFUser::STATUS_RESTRICTED])]
    public function testItReturnsFaultIfCurrentUserIsNotAllowedToSeeHerTimesheeting(string $status): void
    {
        $current_user = UserTestBuilder::anActiveUser()
            ->build();

        $alice = UserTestBuilder::aUser()
            ->withId(self::ALICE_ID)
            ->withStatus($status)
            ->build();

        $perms_verifier = VerifyManagerCanSeeTimetrackingOfUserStub::notAllowed();

        $retriever = new ViewableUserRetriever(
            RetrieveUserByIdStub::withUser($alice),
            $perms_verifier,
        );

        self::assertTrue(Result::isErr($retriever->getViewableUser($current_user, self::ALICE_ID)));
        self::assertTrue($perms_verifier->isCalled());
    }
}
