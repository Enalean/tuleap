<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Artifact\Closure;

use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\UserName;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class BadSemanticCommentTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private const USER_LOGIN = 'jambrosius';

    private function getBody(): string
    {
        $user = UserTestBuilder::aUser()->withUserName(self::USER_LOGIN)->build();

        return BadSemanticComment::fromUser(UserName::fromUser($user))->getBody();
    }

    public function testItBuildsFromUser(): void
    {
        self::assertStringContainsString('@' . self::USER_LOGIN, $this->getBody());
    }
}
