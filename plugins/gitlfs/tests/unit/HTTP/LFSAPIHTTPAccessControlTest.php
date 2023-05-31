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

namespace Tuleap\GitLFS\HTTP;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Git\HTTP\HTTPAccessControl;
use Tuleap\GitLFS\Batch\Request\BatchRequest;
use Tuleap\Git\Permissions\AccessControlVerifier;
use Tuleap\Request\NotFoundException;

final class LFSAPIHTTPAccessControlTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private MockObject&LSFAPIHTTPAuthorization $lfs_http_api_access_control;
    private MockObject&HTTPAccessControl $http_access_control;
    private MockObject&\UserManager $user_manager;
    private MockObject&AccessControlVerifier $access_control_verifier;

    protected function setUp(): void
    {
        $this->lfs_http_api_access_control = $this->createMock(LSFAPIHTTPAuthorization::class);
        $this->http_access_control         = $this->createMock(HTTPAccessControl::class);
        $this->user_manager                = $this->createMock(\UserManager::class);
        $this->access_control_verifier     = $this->createMock(AccessControlVerifier::class);
    }

    public function testAnonymousUserCanDownloadFromAPublicRepository(): void
    {
        $this->lfs_http_api_access_control->method('getUserFromAuthorizationToken')->willReturn(null);
        $this->http_access_control->method('getUser')->willReturn(null);

        $batch_api_access_control = new LFSAPIHTTPAccessControl(
            $this->access_control_verifier
        );

        $repository    = $this->createMock(\GitRepository::class);
        $batch_request = $this->createMock(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(true);

        self::assertTrue($batch_api_access_control->canAccess($repository, $batch_request, null));
    }

    public function testAuthenticatedViaBasicAuthUserWithReadPermissionCanDownload(): void
    {
        $this->lfs_http_api_access_control->method('getUserFromAuthorizationToken')->willReturn(null);

        $pfo_user = $this->createMock(\PFO_User::class);
        $pfo_user->method('getUserName')->willReturn('username');
        $this->http_access_control->method('getUser')->willReturn($pfo_user);

        $user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserByUserName')->willReturn($user);

        $batch_api_access_control = new LFSAPIHTTPAccessControl(
            $this->access_control_verifier
        );

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->willReturn(true);
        $batch_request = $this->createMock(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(true);
        $batch_request->method('isWrite')->willReturn(false);

        self::assertTrue($batch_api_access_control->canAccess($repository, $batch_request, $user));
    }

    public function testAuthenticatedViaBasicAuthUserWithWritePermissionCanUpload(): void
    {
        $this->lfs_http_api_access_control->method('getUserFromAuthorizationToken')->willReturn(null);

        $pfo_user = $this->createMock(\PFO_User::class);
        $pfo_user->method('getUserName')->willReturn('username');
        $this->http_access_control->method('getUser')->willReturn($pfo_user);

        $user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserByUserName')->willReturn($user);

        $this->access_control_verifier->method('canWrite')->willReturn(true);

        $batch_api_access_control = new LFSAPIHTTPAccessControl(
            $this->access_control_verifier
        );

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->willReturn(true);
        $batch_request = $this->createMock(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(false);
        $batch_request->method('isWrite')->willReturn(true);
        $batch_request->method('getReference')->willReturn(null);

        self::assertTrue($batch_api_access_control->canAccess($repository, $batch_request, $user));
    }

    public function testUserAuthenticatedViaLFSAuthorizationTokenCanAccessTheRepository(): void
    {
        $user = $this->createMock(\PFUser::class);
        $this->lfs_http_api_access_control->method('getUserFromAuthorizationToken')->willReturn($user);

        $batch_api_access_control = new LFSAPIHTTPAccessControl(
            $this->access_control_verifier
        );

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->willReturn(true);
        $batch_request = $this->createMock(BatchRequest::class);
        $batch_request->method('isRead')->willReturn(true);
        $batch_request->method('isWrite')->willReturn(false);

        self::assertTrue($batch_api_access_control->canAccess($repository, $batch_request, $user));
    }

    public function testUserWithoutReadPermissionDoesNotSeeTheRepository(): void
    {
        $this->lfs_http_api_access_control->method('getUserFromAuthorizationToken')->willReturn(null);

        $pfo_user = $this->createMock(\PFO_User::class);
        $pfo_user->method('getUserName')->willReturn('username');
        $this->http_access_control->method('getUser')->willReturn($pfo_user);

        $user = $this->createMock(\PFUser::class);
        $this->user_manager->method('getUserByUserName')->willReturn($user);

        $batch_api_access_control = new LFSAPIHTTPAccessControl(
            $this->access_control_verifier
        );

        $repository = $this->createMock(\GitRepository::class);
        $repository->method('userCanRead')->willReturn(false);
        $batch_request = $this->createMock(BatchRequest::class);

        $this->expectException(NotFoundException::class);

        $batch_api_access_control->canAccess($repository, $batch_request, $user);
    }
}
