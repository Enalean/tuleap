<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

namespace Tuleap\AI\Requestor;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\AnonymousUserTestProvider;
use Tuleap\User\CurrentUserWithLoggedInInformation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class EndUserAIRequestorTest extends TestCase
{
    public function testRejectsNotLoggedInUser(): void
    {
        $result = EndUserAIRequestor::fromCurrentUser(
            CurrentUserWithLoggedInInformation::fromAnonymous(new AnonymousUserTestProvider())
        );

        self::assertFalse($result->unwrapOr(false));
    }

    public function testAcceptsLoggedInUsers(): void
    {
        $result = EndUserAIRequestor::fromCurrentUser(
            CurrentUserWithLoggedInInformation::fromLoggedInUser(UserTestBuilder::anActiveUser()->withId(123)->build())
        );

        $requestor = $result->unwrapOr(null);

        self::assertNotNull($requestor);
        self::assertEquals('123', $requestor->getIdentifier());
    }
}
