<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\OpenIDConnectClient\UserMapping;

use PHPUnit\Framework\TestCase;
use Tuleap\Test\Builders\UserTestBuilder;

final class CanRemoveUserMappingCheckerTest extends TestCase
{
    /**
     * @var CanRemoveUserMappingChecker
     */
    private $checker;

    protected function setUp(): void
    {
        $this->checker = new CanRemoveUserMappingChecker();
    }

    public function testCanRemoveAMappingWhenTheUserHasAPasswordSetOnTheAccount(): void
    {
        $user = UserTestBuilder::aUser()->build();
        $user->setUserPw('some_password_hash');

        $this->assertTrue($this->checker->canAUserMappingBeRemoved($user, [$this->buildMappingUsage()]));
    }

    public function testCanRemoveWhenThereIsMoreThanOneLink(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->assertTrue($this->checker->canAUserMappingBeRemoved($user, [$this->buildMappingUsage(), $this->buildMappingUsage()]));
    }

    public function testCannotRemoveWhenUserHasNoPasswordAndThereIsOnlyOneLink(): void
    {
        $user = UserTestBuilder::aUser()->build();

        $this->assertFalse($this->checker->canAUserMappingBeRemoved($user, [$this->buildMappingUsage()]));
    }

    private function buildMappingUsage(): UserMappingUsage
    {
        return new UserMappingUsage(1, 1, 'Provider name', 'icon', false, 102, 10);
    }
}
