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
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\User\Operation\UserOperation;

class UserTokenVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $hasher;
    private $user_manager;
    private $current_time;

    protected function setUp(): void
    {
        $this->dao          = \Mockery::mock(UserAuthorizationDAO::class);
        $this->hasher       = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->user_manager = \Mockery::mock(\UserManager::class);
        $this->current_time = new \DateTimeImmutable('2018-11-30', new \DateTimeZone('UTC'));
    }

    public function testUserCanBeRetrievedFromUserToken()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAlive')->andReturns(true);
        $this->user_manager->shouldReceive('getUserById')->andReturns($user);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $this->assertSame(
            $user,
            $verifier->getUser($this->current_time, $user_token, $repository, $operation)
        );
    }

    public function testUserTokenCanBeUsedMoreThanOnce()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAlive')->andReturns(true);
        $this->user_manager->shouldReceive('getUserById')->andReturns($user);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenANotExpiredAuthorizationCanNotBeFound()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns(null);
        $this->expectException(UserAuthorizationNotFoundException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenAVerificationStringDoesNotMatch()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'notvalid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'notvalid')->andReturns(false);

        $this->expectException(InvalidUserUserAuthorizationException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenOperationDoesNotMatch()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation_A');

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation_B',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $this->expectException(InvalidUserUserAuthorizationException::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenUserCannotBeFound()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation');

        $this->user_manager->shouldReceive('getUserById')->andReturns(null);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $this->expectException(UserNotFoundExceptionUser::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }

    public function testGettingUserFailsWhenUserIsNotAlive()
    {
        $verifier = new UserTokenVerifier($this->dao, $this->hasher, $this->user_manager);

        $user_token = \Mockery::mock(SplitToken::class);
        $user_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $user_token->shouldReceive('getVerificationString')->andReturns($verification_string);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(3);
        $operation = \Mockery::mock(UserOperation::class);
        $operation->shouldReceive('getName')->andReturns('operation');

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isAlive')->andReturns(false);
        $this->user_manager->shouldReceive('getUserById')->andReturns($user);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'user_id'         => 123,
            'operation_name'  => 'operation',
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $this->expectException(UserNotFoundExceptionUser::class);

        $verifier->getUser($this->current_time, $user_token, $repository, $operation);
    }
}
