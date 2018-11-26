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

namespace Tuleap\GitLFS\Authorization\Action;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;

class ActionAuthorizationVerifierTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $dao;
    private $hasher;
    private $repository_factory;
    private $current_time;

    protected function setUp()
    {
        $this->dao                = \Mockery::mock(ActionAuthorizationDAO::class);
        $this->hasher             = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $this->repository_factory = \Mockery::mock(\GitRepositoryFactory::class);
        $this->current_time       = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
    }

    public function testAuthorizedActionCanBeRetrievedFromTheAuthorizationToken()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $authorization_token->shouldReceive('getVerificationString')->andReturns($verification_string);

        $oid  = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';
        $size = 123456;

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => $oid,
            'object_size'     => $size
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);
        $expected_repository = \Mockery::mock(\GitRepository::class);
        $this->repository_factory->shouldReceive('getRepositoryById')->with(3)->andReturns($expected_repository);

        $authorized_action = $verifier->getAuthorization($this->current_time, $authorization_token, $oid, 'upload');

        $this->assertSame($oid, $authorized_action->getOID());
        $this->assertSame($size, $authorized_action->getSize());
        $this->assertSame($expected_repository, $authorized_action->getRepository());
    }

    /**
     * @expectedException \Tuleap\GitLFS\Authorization\Action\ActionAuthorizationNotFoundException
     */
    public function testVerificationFailsWhenANotExpiredAuthorizationCanNotBeFound()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns(null);

        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verifier->getAuthorization($this->current_time, $authorization_token, 'oid', 'upload');
    }

    /**
     * @expectedException \Tuleap\GitLFS\Authorization\Action\InvalidActionAuthorizationException
     */
    public function testVerificationFailsWhenVerificationStringDoesNotMatch()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $authorization_token->shouldReceive('getVerificationString')->andReturns($verification_string);

        $oid  = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => $oid,
            'object_size'     => 123456
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(false);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, 'upload');
    }

    /**
     * @expectedException \Tuleap\GitLFS\Authorization\Action\InvalidActionAuthorizationException
     */
    public function testVerificationFailsWhenOIDDoesNotMatch()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $current_time = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $authorization_token->shouldReceive('getVerificationString')->andReturns($verification_string);

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b',
            'object_size'     => 123456
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $verifier->getAuthorization($current_time, $authorization_token, 'not_requested_oid', 'upload');
    }

    /**
     * @expectedException \Tuleap\GitLFS\Authorization\Action\InvalidActionAuthorizationException
     */
    public function testVerificationFailsWhenActionTypeDoesNotMatch()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $authorization_token->shouldReceive('getVerificationString')->andReturns($verification_string);

        $oid  = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => $oid,
            'object_size'     => 123456
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, 'not_requested_action_type');
    }

    /**
     * @expectedException \Tuleap\GitLFS\Authorization\Action\ActionAuthorizationMatchingUnknownRepositoryException
     */
    public function testVerificationFailsWHenTheCorrespondingRepositoryCanNotBeFound()
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = \Mockery::mock(SplitToken::class);
        $authorization_token->shouldReceive('getID')->andReturns(1);
        $verification_string = \Mockery::mock(SplitTokenVerificationString::class);
        $authorization_token->shouldReceive('getVerificationString')->andReturns($verification_string);

        $oid  = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->shouldReceive('searchAuthorizationByIDAndExpiration')->andReturns([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => $oid,
            'object_size'     => 123456
        ]);
        $this->hasher->shouldReceive('verifyHash')->with($verification_string, 'valid')->andReturns(true);
        $this->repository_factory->shouldReceive('getRepositoryById')->andReturns(null);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, 'upload');
    }
}
