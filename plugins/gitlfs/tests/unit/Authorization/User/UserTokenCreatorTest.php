<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

namespace Tuleap\GitLFS\Authorization\User;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserTokenCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testUserTokenIsCreated(): void
    {
        $hasher = $this->createStub(SplitTokenVerificationStringHasher::class);
        $hasher->method('computeHash')->willReturn('hashed_verification_string');
        $dao = $this->createStub(UserAuthorizationDAO::class);
        $dao->method('create')->willReturn(100);

        $creator = new UserTokenCreator($hasher, $dao);

        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(1);
        $expiration = new \DateTimeImmutable('2018-11-30', new \DateTimeZone('UTC'));
        $user       = $this->createStub(\PFUser::class);
        $user->method('getId')->willReturn(102);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation_name');

        $token = $creator->createUserAuthorizationToken($repository, $expiration, $user, $operation);

        $this->assertInstanceOf(SplitToken::class, $token);
    }
}
