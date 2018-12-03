<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\GitLFS\Authorization\User;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;

class UserTokenCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testUserTokenIsCreated()
    {
        $hasher = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('hashed_verification_string');
        $dao = \Mockery::mock(UserAuthorizationDAO::class);
        $dao->shouldReceive('create')->andReturns(100);

        $creator = new UserTokenCreator($hasher, $dao);

        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(1);
        $expiration = new \DateTimeImmutable('2018-11-30', new \DateTimeZone('UTC'));
        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation_name');

        $token = $creator->createUserAuthorizationToken($repository, $expiration, $user, $operation);

        $this->assertInstanceOf(SplitToken::class, $token);
    }
}
