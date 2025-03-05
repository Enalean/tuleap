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
use Tuleap\Authentication\SplitToken\SplitTokenVerificationStringHasher;
use Tuleap\GitLFS\Authorization\Action\Type\ActionAuthorizationTypeUpload;
use Tuleap\GitLFS\LFSObject\LFSObject;
use Tuleap\GitLFS\LFSObject\LFSObjectID;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ActionAuthorizationTokenCreatorTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testAuthorizationTokenIsCreated(): void
    {
        $hasher = $this->createStub(SplitTokenVerificationStringHasher::class);
        $hasher->method('computeHash')->willReturn('hashed_verification_string');
        $dao = $this->createStub(ActionAuthorizationDAO::class);
        $dao->method('create')->willReturn(100);

        $creator = new ActionAuthorizationTokenCreator($hasher, $dao);

        $authorization_request = $this->createStub(ActionAuthorizationRequest::class);
        $repository            = $this->createStub(\GitRepository::class);
        $repository->method('getId')->willReturn(1);
        $authorization_request->method('getGitRepository')->willReturn($repository);
        $authorization_request->method('getExpiration')
            ->willReturn(new \DateTimeImmutable('2018-11-22'), new \DateTimeZone('UTC'));
        $action_type = new ActionAuthorizationTypeUpload();
        $authorization_request->method('getActionType')->willReturn($action_type);
        $request_object_id = $this->createStub(LFSObjectID::class);
        $request_object_id->method('getValue')->willReturn('oid');
        $request_object = $this->createStub(LFSObject::class);
        $request_object->method('getOID')->willReturn($request_object_id);
        $request_object->method('getSize')->willReturn(123456);
        $authorization_request->method('getObject')->willReturn($request_object);

        $token = $creator->createActionAuthorizationToken($authorization_request);

        $this->assertInstanceOf(SplitToken::class, $token);
    }
}
