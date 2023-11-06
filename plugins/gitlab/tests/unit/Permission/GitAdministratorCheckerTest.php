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

namespace Tuleap\Gitlab\Permission;

use Tuleap\Git\Tests\Stub\VerifyUserIsGitAdministratorStub;
use Tuleap\NeverThrow\Err;
use Tuleap\NeverThrow\Ok;
use Tuleap\NeverThrow\Result;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class GitAdministratorCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private VerifyUserIsGitAdministratorStub $admin_verifier;

    protected function setUp(): void
    {
        $this->admin_verifier = VerifyUserIsGitAdministratorStub::withAlwaysGitAdministrator();
    }

    private function checkUserIsGitAdministrator(): Ok|Err
    {
        $checker = new GitAdministratorChecker($this->admin_verifier);
        return $checker->checkUserIsGitAdministrator(
            ProjectTestBuilder::aProject()->build(),
            UserTestBuilder::buildWithDefaults()
        );
    }

    public function testItReturnsNull(): void
    {
        $result = $this->checkUserIsGitAdministrator();
        self::assertTrue(Result::isOk($result));
    }

    public function testItReturnsFaultWhenUserIsNotGitAdministrator(): void
    {
        $this->admin_verifier = VerifyUserIsGitAdministratorStub::withNeverGitAdministrator();

        $result = $this->checkUserIsGitAdministrator();
        self::assertTrue(Result::isErr($result));
        self::assertInstanceOf(UserIsNotGitAdministratorFault::class, $result->error);
    }
}
