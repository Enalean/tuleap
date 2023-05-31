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

namespace Tuleap\GitLFS\Authorization\Action;

use Tuleap\Authentication\SplitToken\SplitToken;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationString;
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationType;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;

final class ActionAuthorizationVerifierTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \PHPUnit\Framework\MockObject\MockObject&ActionAuthorizationDAO $dao;
    private SplitTokenVerificationStringHasher&\PHPUnit\Framework\MockObject\MockObject $hasher;
    private \GitRepositoryFactory&\PHPUnit\Framework\MockObject\MockObject $repository_factory;
    private \DateTimeImmutable $current_time;

    protected function setUp(): void
    {
        $this->dao                = $this->createMock(ActionAuthorizationDAO::class);
        $this->hasher             = $this->createMock(SplitTokenVerificationStringHasher::class);
        $this->repository_factory = $this->createMock(\GitRepositoryFactory::class);
        $this->current_time       = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
    }

    public function testAuthorizedActionCanBeRetrievedFromTheAuthorizationToken(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $authorization_token->method('getVerificationString')->willReturn($verification_string);
        $action_type = new ActionAuthorizationTypeUpload();

        $oid_value = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';
        $size      = 123456;

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => $action_type->getName(),
            'object_oid'      => $oid_value,
            'object_size'     => $size,
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);
        $expected_repository = $this->createMock(\GitRepository::class);
        $this->repository_factory->method('getRepositoryById')->with(3)->willReturn($expected_repository);

        $authorized_action = $verifier->getAuthorization($this->current_time, $authorization_token, $oid_value, $action_type);

        self::assertSame($oid_value, $authorized_action->getLFSObject()->getOID()->getValue());
        self::assertSame($size, $authorized_action->getLFSObject()->getSize());
        self::assertSame($expected_repository, $authorized_action->getRepository());
    }

    public function testVerificationFailsWhenANotExpiredAuthorizationCanNotBeFound(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn(null);

        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);

        $this->expectException(ActionAuthorizationNotFoundException::class);

        $verifier->getAuthorization($this->current_time, $authorization_token, 'oid', new ActionAuthorizationTypeUpload());
    }

    public function testVerificationFailsWhenVerificationStringDoesNotMatch(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $authorization_token->method('getVerificationString')->willReturn($verification_string);
        $action_type = new ActionAuthorizationTypeUpload();

        $oid = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => $action_type->getName(),
            'object_oid'      => $oid,
            'object_size'     => 123456,
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(false);

        $this->expectException(InvalidActionAuthorizationException::class);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, $action_type);
    }

    public function testVerificationFailsWhenOIDDoesNotMatch(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $current_time        = new \DateTimeImmutable('2018-11-22', new \DateTimeZone('UTC'));
        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $authorization_token->method('getVerificationString')->willReturn($verification_string);
        $action_type = new ActionAuthorizationTypeUpload();

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => $action_type->getName(),
            'object_oid'      => 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b',
            'object_size'     => 123456,
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $this->expectException(InvalidActionAuthorizationException::class);

        $verifier->getAuthorization($current_time, $authorization_token, 'not_requested_oid', $action_type);
    }

    public function testVerificationFailsWhenActionTypeDoesNotMatch(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $authorization_token->method('getVerificationString')->willReturn($verification_string);
        $action_type = $this->createMock(ActionAuthorizationType::class);
        $action_type->method('getName')->willReturn('not_requested_action_type');

        $oid = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => 'upload',
            'object_oid'      => $oid,
            'object_size'     => 123456,
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);

        $this->expectException(InvalidActionAuthorizationException::class);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, $action_type);
    }

    public function testVerificationFailsWHenTheCorrespondingRepositoryCanNotBeFound(): void
    {
        $verifier = new ActionAuthorizationVerifier($this->dao, $this->hasher, $this->repository_factory);

        $authorization_token = $this->createMock(SplitToken::class);
        $authorization_token->method('getID')->willReturn(1);
        $verification_string = $this->createMock(SplitTokenVerificationString::class);
        $authorization_token->method('getVerificationString')->willReturn($verification_string);
        $action_type = new ActionAuthorizationTypeUpload();

        $oid = 'f1e606a320357367335295bbc741cae6824ee33ce10cc43c9281d08638b73c6b';

        $this->dao->method('searchAuthorizationByIDAndExpiration')->willReturn([
            'id'              => 1,
            'verifier'        => 'valid',
            'expiration_date' => PHP_INT_MAX,
            'repository_id'   => 3,
            'action_type'     => $action_type->getName(),
            'object_oid'      => $oid,
            'object_size'     => 123456,
        ]);
        $this->hasher->method('verifyHash')->with($verification_string, 'valid')->willReturn(true);
        $this->repository_factory->method('getRepositoryById')->willReturn(null);

        $this->expectException(ActionAuthorizationMatchingUnknownRepositoryException::class);

        $verifier->getAuthorization($this->current_time, $authorization_token, $oid, $action_type);
    }
}
