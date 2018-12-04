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
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;

class ActionAuthorizationTokenCreatorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testAuthorizationTokenIsCreated()
    {
        $hasher  = \Mockery::mock(SplitTokenVerificationStringHasher::class);
        $hasher->shouldReceive('computeHash')->andReturns('hashed_verification_string');
        $dao = \Mockery::mock(ActionAuthorizationDAO::class);
        $dao->shouldReceive('create')->andReturns(100);

        $creator = new ActionAuthorizationTokenCreator($hasher, $dao);

        $authorization_request = \Mockery::mock(ActionAuthorizationRequest::class);
        $repository = \Mockery::mock(\GitRepository::class);
        $repository->shouldReceive('getId')->andReturns(1);
        $authorization_request->shouldReceive('getGitRepository')->andReturns($repository);
        $authorization_request->shouldReceive('getExpiration')
            ->andReturns(new \DateTimeImmutable('2018-11-22'), new \DateTimeZone('UTC'));
        $action_type = new ActionAuthorizationTypeUpload();
        $authorization_request->shouldReceive('getActionType')->andReturns($action_type);
        $request_object_id = \Mockery::mock(LFSObjectID::class);
        $request_object_id->shouldReceive('getValue')->andReturns('oid');
        $request_object = \Mockery::mock(LFSObject::class);
        $request_object->shouldReceive('getOID')->andReturns($request_object_id);
        $request_object->shouldReceive('getSize')->andReturns(123456);
        $authorization_request->shouldReceive('getObject')->andReturns($request_object);

        $token = $creator->createActionAuthorizationToken($authorization_request);

        $this->assertInstanceOf(SplitToken::class, $token);
    }
}
