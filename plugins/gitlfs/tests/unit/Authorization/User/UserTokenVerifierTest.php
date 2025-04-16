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
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class UserTokenVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private UserAuthorizationDAO&\PHPUnit\Framework\MockObject\MockObject $dao;
    private SplitTokenVerificationStringHasher&\PHPUnit\Framework\MockObject\MockObject $hasher;
    private \UserManager&\PHPUnit\Framework\MockObject\Stub $user_manager;
    private \DateTimeImmutable $current_time;

    protected function setUp(): void
    {
        $this->dao          = $this->createMock(UserAuthorizationDAO::class);
        $this->hasher       = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->user_manager = $this->createStub(\UserManager::class);
        $this->current_time = new \DateTimeImmutable('2018-11-30', new \DateTimeZone('UTC'));
    }

    public function testUserCanBeRetrievedFromUserToken(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation');

        $user = $this->createStub(\PFUser::class);
        $user->method('isAlive')->willReturn(true);
        $this->user_manager->method('getUserById')->willReturn($user);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        self::assertSame(
            $user,
            $verifier->getUser($this->current_time, $user_token, $repository, $operation)
        );
    }

    public function testUserTokenCanBeUsedMoreThanOnce(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation');

        $user = $this->createStub(\PFUser::class);
        $user->method('isAlive')->willReturn(true);
        $this->user_manager->method('getUserById')->willReturn($user);

        $this->dao->expects($this->atLeastOnce())->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->expects($this->atLeastOnce())->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenANotExpiredAuthorizationCanNotBeFound(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn(null);
        $this->expectException(UserAuthorizationNotFoundException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenAVerificationStringDoesNotMatch(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'notvalid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'notvalid')->willReturn(false);

        $this->expectException(InvalidUserUserAuthorizationException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenOperationDoesNotMatch(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation_A');

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation_B',
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $this->expectException(InvalidUserUserAuthorizationException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenUserCannotBeFound(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation');

        $this->user_manager->method('getUserById')->willReturn(null);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $this->expectException(UserNotFoundExceptionUser::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenUserIsNotAlive(): void
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = $this->createStub(SplitToken::class);
        $user_token->method('getID')->willReturn(1);
        $verification_string = $this->createStub(SplitTokenVerificationString::class);
        $user_token->method('getVerificationString')->willReturn($verification_string);
        $repository = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(3);
        $operation = $this->createStub(UserOperation::class);
        $operation->method('getName')->willReturn('operation');

        $user = $this->createStub(\PFUser::class);
        $user->method('isAlive')->willReturn(false);
        $this->user_manager->method('getUserById')->willReturn($user);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $this->expectException(UserNotFoundExceptionUser::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }
}
